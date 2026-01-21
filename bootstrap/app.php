<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'admin.only' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\TrackPracticeSession::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Log 405 Method Not Allowed errors with details
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            \Illuminate\Support\Facades\Log::warning('405 Method Not Allowed', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'allowed_methods' => $e->getHeaders()['Allow'] ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });
        
        // Handle 419 CSRF token expired errors gracefully
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            \Illuminate\Support\Facades\Log::warning('419 CSRF Token Mismatch', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);
            
            // If it's a login request, redirect back with a helpful message
            if ($request->is('admin/login')) {
                return redirect()
                    ->route('admin.login.show')
                    ->with('error', 'Your session has expired. Please try logging in again.');
            }
            
            // For other requests, show a generic error
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session has expired. Please refresh the page and try again.',
                ], 419);
            }
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Your session has expired. Please refresh the page and try again.');
        });
    })->create();

