<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BackupController extends Controller
{
    /**
     * BackupService instance
     */
    protected BackupService $backupService;

    /**
     * Constructor
     */
    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Create a manual backup
     *
     * POST /api/backups/create
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'notes' => 'nullable|string|max:500',
            ]);

            $backup = $this->backupService->createBackup(
                Auth::user(),
                Backup::TYPE_MANUAL,
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'data' => [
                    'id' => $backup->id,
                    'filename' => $backup->filename,
                    'file_size' => $backup->file_size,
                    'file_size_human' => $backup->human_readable_size,
                    'status' => $backup->status,
                    'created_at' => $backup->created_at,
                    'backup_type' => $backup->backup_type,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all available backups
     *
     * GET /api/backups
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 15);
            $status = $request->query('status');

            $query = Backup::orderBy('created_at', 'desc');

            if ($status) {
                $query->where('status', $status);
            }

            $backups = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $backups->map(function ($backup) {
                    return [
                        'id' => $backup->id,
                        'filename' => $backup->filename,
                        'file_size' => $backup->file_size,
                        'file_size_human' => $backup->human_readable_size,
                        'status' => $backup->status,
                        'backup_type' => $backup->backup_type,
                        'created_by' => $backup->createdBy?->name,
                        'created_at' => $backup->created_at,
                        'notes' => $backup->notes,
                        'file_exists' => $backup->fileExists(),
                    ];
                }),
                'pagination' => [
                    'total' => $backups->total(),
                    'per_page' => $backups->perPage(),
                    'current_page' => $backups->currentPage(),
                    'last_page' => $backups->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve backups: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get backup details
     *
     * GET /api/backups/{id}
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $backup = $this->backupService->getBackupById($id);

            if (!$backup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $backup->id,
                    'filename' => $backup->filename,
                    'file_size' => $backup->file_size,
                    'file_size_human' => $backup->human_readable_size,
                    'status' => $backup->status,
                    'backup_type' => $backup->backup_type,
                    'created_by' => $backup->createdBy?->name,
                    'created_at' => $backup->created_at,
                    'notes' => $backup->notes,
                    'file_exists' => $backup->fileExists(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore from a backup
     *
     * POST /api/backups/{id}/restore
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function restore(Request $request, string $id): JsonResponse
    {
        try {
            $backup = $this->backupService->getBackupById($id);

            if (!$backup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup not found',
                ], 404);
            }

            if (!$backup->fileExists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file does not exist',
                ], 404);
            }

            $this->backupService->restoreBackup($backup, Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Backup restored successfully',
                'data' => [
                    'id' => $backup->id,
                    'filename' => $backup->filename,
                    'status' => $backup->status,
                    'restored_at' => now(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a backup
     *
     * DELETE /api/backups/{id}
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $backup = $this->backupService->getBackupById($id);

            if (!$backup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup not found',
                ], 404);
            }

            $this->backupService->deleteBackup($backup);

            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get backup statistics
     *
     * GET /api/backups/stats
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->backupService->getBackupStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve backup statistics: ' . $e->getMessage(),
            ], 500);
        }
    }
}
