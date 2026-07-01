<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // X-Frame-Options: Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY', false);

        // X-Content-Type-Options: Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff', false);

        // X-XSS-Protection: Enable XSS filter (legacy, but still useful)
        $response->headers->set('X-XSS-Protection', '1; mode=block', false);

        // Referrer-Policy: Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);

        // Permissions-Policy: Restrict browser features (microphone allowed for same-origin speaking activities)
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(self), camera=()', false);

        // HSTS: Force HTTPS (only in production)
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        // Content Security Policy
        // Note: Adjust this based on your actual needs (CDNs, external scripts, etc.)
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.tailwindcss.com; " .
               "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
               "img-src 'self' data: https:; " .
               "media-src 'self' blob:; " .
               "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; " .
               "connect-src 'self' https://www.google.com https://*.google.com https://speech.googleapis.com; " .
               "frame-ancestors 'none';";
        
        $response->headers->set('Content-Security-Policy', $csp, false);

        return $response;
    }
}

