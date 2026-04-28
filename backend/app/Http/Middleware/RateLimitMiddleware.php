<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $key = 'global'): Response
    {
        $identifier = $request->ip();
        
        // Different rate limits for different endpoints
        $limits = [
            'login' => [5, 1], // 5 attempts per minute
            'register' => [3, 5], // 3 attempts per 5 minutes
            'api' => [60, 1], // 60 requests per minute
            'global' => [100, 1], // 100 requests per minute
        ];

        [$maxAttempts, $decayMinutes] = $limits[$key] ?? $limits['global'];

        if (RateLimiter::tooManyAttempts($identifier . ':' . $key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($identifier . ':' . $key);
            
            return response()->json([
                'message' => 'Too many attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($identifier . ':' . $key, $decayMinutes * 60);

        return $next($request);
    }
}
