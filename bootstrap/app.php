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
    })->create();

