<?php

namespace App\Http\Middleware;

use App\Support\VoiceSampleViewerAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureVoiceSampleViewer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user();

        if (! VoiceSampleViewerAccess::allows($user)) {
            abort(403, 'You do not have access to voice sample data.');
        }

        return $next($request);
    }
}
