<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize all input data
        $input = $request->all();
        
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                // Remove potential XSS attacks
                $value = strip_tags($value);
                // Trim whitespace
                $value = trim($value);
                // Remove null bytes
                $value = str_replace(chr(0), '', $value);
            }
        });

        $request->merge($input);

        return $next($request);
    }
}
