<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Exception;

class BackupService
{
    /**
     * Backup directory path
     */
    protected string $backupDir;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->backupDir = storage_path('backups');
        $this->ensureBackupDirectoryExists();
    }

    /**
     * Ensure backup directory exists
     */
    protected function ensureBackupDirectoryExists(): void
    {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Create a database backup
     *
     * @param User|null $user User who triggered the backup
     * @param string $type Backup type (manual or scheduled)
     * @param string|null $notes Optional notes
     * @return Backup
     * @throws Exception
     */
    public function createBackup(?User $user = null, string $type = Backup::TYPE_MANUAL, ?string $notes = null): Backup
    {
        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$timestamp}.sql";
            $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

            // Create backup record
            $backup = Backup::create([
                'filename' => $filename,
                'file_path' => $filePath,
                'file_size' => 0,
                'status' => Backup::STATUS_PENDING,
                'created_by' => $user?->id,
                'backup_type' => $type,
                'notes' => $notes,
            ]);

            // Execute backup command
            $this->executeBackupCommand($filePath);

            // Update backup record with file size
            if (file_exists($filePath)) {
                $fileSize = filesize($filePath);
                $backup->update([
                    'file_size' => $fileSize,
                    'status' => Backup::STATUS_COMPLETED,
                ]);
            } else {
                throw new Exception('Backup file was not created');
            }

            return $backup;
        } catch (Exception $e) {
            if (isset($backup)) {
                $backup->update(['status' => Backup::STATUS_FAILED]);
            }
            throw $e;
        }
    }

    /**
     * Execute the backup command
     *
     * @param string $filePath Path where backup should be saved
     * @throws Exception
     */
    protected function executeBackupCommand(string $filePath): void
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filePath)
        );

        // Execute command
        $result = Process::run($command);

        if (!$result->successful()) {
            throw new Exception('Backup command failed: ' . $result->errorOutput());
        }
    }

    /**
     * Restore from a backup
     *
     * @param Backup $backup Backup to restore from
     * @param User|null $user User who triggered the restore
     * @return bool
     * @throws Exception
     */
    public function restoreBackup(Backup $backup, ?User $user = null): bool
    {
        try {
            if (!$backup->fileExists()) {
                throw new Exception('Backup file does not exist');
            }

            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', 3306);

            // Build mysql restore command
            $command = sprintf(
                'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($backup->file_path)
            );

            // Execute command
            $result = Process::run($command);

            if (!$result->successful()) {
                throw new Exception('Restore command failed: ' . $result->errorOutput());
            }

            // Update backup status
            $backup->update(['status' => Backup::STATUS_RESTORED]);

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Delete a backup
     *
     * @param Backup $backup Backup to delete
     * @return bool
     */
    public function deleteBackup(Backup $backup): bool
    {
        try {
            if ($backup->fileExists()) {
                unlink($backup->file_path);
            }
            $backup->delete();
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get all backups
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllBackups()
    {
        return Backup::orderBy('created_at', 'desc')->get();
    }

    /**
     * Get backup by ID
     *
     * @param string $id Backup ID
     * @return Backup|null
     */
    public function getBackupById(string $id): ?Backup
    {
        return Backup::find($id);
    }

    /**
     * Clean old backups (keep only last N backups)
     *
     * @param int $keepCount Number of backups to keep
     * @return int Number of backups deleted
     */
    public function cleanOldBackups(int $keepCount = 10): int
    {
        $backups = Backup::orderBy('created_at', 'desc')->get();
        $deleted = 0;

        foreach ($backups->slice($keepCount) as $backup) {
            if ($this->deleteBackup($backup)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get backup statistics
     *
     * @return array
     */
    public function getBackupStats(): array
    {
        $backups = Backup::all();
        $totalSize = $backups->sum('file_size');
        $completedCount = $backups->where('status', Backup::STATUS_COMPLETED)->count();
        $failedCount = $backups->where('status', Backup::STATUS_FAILED)->count();

        return [
            'total_backups' => $backups->count(),
            'completed_backups' => $completedCount,
            'failed_backups' => $failedCount,
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'last_backup' => $backups->first()?->created_at,
        ];
    }

    /**
     * Format bytes to human-readable format
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
