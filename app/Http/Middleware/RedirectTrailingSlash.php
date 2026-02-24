<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectTrailingSlash
{
    /**
     * Redirect /o/{org}/admin/ to /o/{org}/admin (and similar) to avoid 404.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        // Redirect /o/{org}/ to /o/{org} (student index) to avoid 404
        if (str_ends_with($path, '/') && preg_match('#^o/[^/]+/?$#', $path)) {
            return redirect('/' . rtrim($path, '/'), 301);
        }

        // Redirect /o/{org}/admin/ to /o/{org}/admin to avoid 404 from route mismatch
        if (str_ends_with($path, '/') && preg_match('#^o/[^/]+/admin/?$#', $path)) {
            return redirect('/' . rtrim($path, '/'), 301);
        }

        return $next($request);
    }
}
