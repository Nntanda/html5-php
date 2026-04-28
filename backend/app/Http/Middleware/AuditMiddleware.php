<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log API requests for audit trail
        $this->logRequest($request, $response);

        return $response;
    }

    /**
     * Log API request
     */
    private function logRequest(Request $request, Response $response): void
    {
        // Skip logging for certain routes
        $skipRoutes = [
            'health',
            'login',
            'forgot-password',
            'reset-password',
        ];

        $path = $request->path();
        foreach ($skipRoutes as $skipRoute) {
            if (str_contains($path, $skipRoute)) {
                return;
            }
        }

        // Only log authenticated requests
        if (!$request->user()) {
            return;
        }

        // Determine action based on HTTP method
        $action = match ($request->method()) {
            'POST' => AuditLog::ACTION_CREATE,
            'PUT', 'PATCH' => AuditLog::ACTION_UPDATE,
            'DELETE' => AuditLog::ACTION_DELETE,
            default => null,
        };

        if (!$action) {
            return;
        }

        // Extract entity type and ID from route
        $routeParts = explode('/', trim($path, '/'));
        $entityType = null;
        $entityId = null;

        // Parse route to extract entity type and ID
        if (count($routeParts) >= 2) {
            // Handle routes like /api/loans/123, /api/members/456, etc.
            if ($routeParts[0] === 'api') {
                $entityType = rtrim($routeParts[1], 's'); // Remove trailing 's' for singular form
                
                // Capitalize first letter for model name
                $entityType = ucfirst($entityType);

                // Handle special cases
                if ($routeParts[1] === 'loans' && isset($routeParts[2])) {
                    $entityType = 'Loan';
                    $entityId = $routeParts[2];
                } elseif ($routeParts[1] === 'members' && isset($routeParts[2])) {
                    $entityType = 'Member';
                    $entityId = $routeParts[2];
                } elseif ($routeParts[1] === 'users' && isset($routeParts[2])) {
                    $entityType = 'User';
                    $entityId = $routeParts[2];
                } elseif ($routeParts[1] === 'savings' && isset($routeParts[3])) {
                    $entityType = 'SavingsTransaction';
                    $entityId = $routeParts[3] ?? null;
                }
            }
        }

        // Capture request data for changes
        $changes = null;
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $changes = [
                'request_data' => $request->except(['password', 'password_confirmation']),
            ];
        }

        // Create audit log
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'changes' => $changes,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
