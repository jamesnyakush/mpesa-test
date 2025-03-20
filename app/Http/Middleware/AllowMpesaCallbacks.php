<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowMpesaCallbacks
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is the M-Pesa callback URL
        if ($request->is('api/v1/confirm')) {
            // Skip CSRF verification for this route
            return $next($request);
        }

        // For all other routes, continue with normal middleware processing
        return $next($request);
    }
}
