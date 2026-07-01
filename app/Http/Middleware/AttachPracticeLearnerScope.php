<?php

namespace App\Http\Middleware;

use App\Support\PracticeLearnerScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachPracticeLearnerScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(
            'practice_learner_scope',
            PracticeLearnerScope::resolve($request)
        );

        return $next($request);
    }
}
