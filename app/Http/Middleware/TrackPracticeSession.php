<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackPracticeSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = config('app.practice_session_cookie', 'talma_session_id');
        $minutes = 60 * 24 * 365; // 1 year

        $sessionId = $request->cookie($cookieName);

        if (!$sessionId) {
            $sessionId = (string) Str::uuid();
            Cookie::queue(
                cookie(
                    name: $cookieName,
                    value: $sessionId,
                    minutes: $minutes,
                    path: '/',
                    domain: null,
                    secure: config('session.secure', false),
                    httpOnly: true,
                    raw: false,
                    sameSite: 'lax'
                )
            );
        }

        $request->attributes->set('practice_session_id', $sessionId);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        return $response;
    }
}
