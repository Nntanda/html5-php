<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    /**
     * Get audit logs with filters
     * GET /api/audit-logs
     */
    public function index(Request $request): JsonResponse
    {
        // Check if user is Super Admin
        if (!$request->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can view audit logs.',
            ], 403);
        }

        $query = AuditLog::query();

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        // Filter by action
        if ($request->has('action')) {
            $query->where('action', $request->query('action'));
        }

        // Filter by entity type
        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->query('entity_type'));
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $startDate = Carbon::parse($request->query('start_date'))->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }

        if ($request->has('end_date')) {
            $endDate = Carbon::parse($request->query('end_date'))->endOfDay();
            $query->where('created_at', '<=', $endDate);
        }

        // Pagination
        $limit = $request->query('limit', 50);
        $offset = $request->query('offset', 0);

        $total = $query->count();
        $logs = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->with('user')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * Get audit log details
     * GET /api/audit-logs/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        // Check if user is Super Admin
        if (!$request->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can view audit logs.',
            ], 403);
        }

        $log = AuditLog::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    /**
     * Get audit logs for specific entity
     * GET /api/audit-logs/entity/{entityType}/{entityId}
     */
    public function entityLogs(Request $request, string $entityType, string $entityId): JsonResponse
    {
        // Check if user is Super Admin
        if (!$request->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can view audit logs.',
            ], 403);
        }

        $logs = AuditLog::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'desc')
            ->with('user')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Get audit logs for specific user
     * GET /api/audit-logs/user/{userId}
     */
    public function userLogs(Request $request, string $userId): JsonResponse
    {
        // Check if user is Super Admin or viewing own logs
        if (!$request->user()->isSuperAdmin() && $request->user()->id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $limit = $request->query('limit', 50);
        $offset = $request->query('offset', 0);

        $total = AuditLog::where('user_id', $userId)->count();
        $logs = AuditLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * Export audit logs
     * GET /api/audit-logs/export
     */
    public function export(Request $request): JsonResponse
    {
        // Check if user is Super Admin
        if (!$request->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can export audit logs.',
            ], 403);
        }

        $query = AuditLog::query();

        // Apply filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->has('action')) {
            $query->where('action', $request->query('action'));
        }

        if ($request->has('start_date')) {
            $startDate = Carbon::parse($request->query('start_date'))->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }

        if ($request->has('end_date')) {
            $endDate = Carbon::parse($request->query('end_date'))->endOfDay();
            $query->where('created_at', '<=', $endDate);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->with('user')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs->map(fn($log) => [
                'id' => $log->id,
                'user' => $log->user?->name,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'changes' => $log->changes,
                'ip_address' => $log->ip_address,
                'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
            ]),
        ]);
    }
}
