<?php
// Excerpt from bootstrap/app.php - relevant auth/middleware parts

$middleware->alias([
    'admin.access' => \App\Http\Middleware\EnsureAdminAccess::class,
    'admin.only' => \App\Http\Middleware\EnsureUserIsAdmin::class,
]);

// Redirect unauthenticated admin requests to admin login (not /login)
$exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
    if ($request->is('admin') || $request->is('admin/*')) {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        return redirect()->guest(route('admin.login.show'));
    }
});
