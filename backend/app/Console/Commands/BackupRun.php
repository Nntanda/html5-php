<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run {--type=scheduled : Backup type (manual or scheduled)}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Create a database backup';

    /**
     * BackupService instance
     */
    protected BackupService $backupService;

    /**
     * Create a new command instance.
     */
    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $type = $this->option('type') === 'manual' ? Backup::TYPE_MANUAL : Backup::TYPE_SCHEDULED;

            $this->info('Starting database backup...');

            $backup = $this->backupService->createBackup(
                null,
                $type,
                "Backup created via artisan command at " . now()
            );

            $this->info("✓ Backup created successfully!");
            $this->info("  Filename: {$backup->filename}");
            $this->info("  Size: {$backup->human_readable_size}");
            $this->info("  Status: {$backup->status}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Backup failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
