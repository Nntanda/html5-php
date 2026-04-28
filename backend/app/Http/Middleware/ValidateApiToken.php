<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Unauthenticated. Token not provided.',
            ], 401);
        }

        // Check token format (basic validation)
        if (strlen($token) < 40) {
            return response()->json([
                'message' => 'Invalid token format.',
            ], 401);
        }

        // Check if token is blacklisted (you can implement token blacklist)
        // if ($this->isTokenBlacklisted($token)) {
        //     return response()->json(['message' => 'Token has been revoked.'], 401);
        // }

        return $next($request);
    }
}
