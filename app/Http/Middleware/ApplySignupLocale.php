<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\SignupLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySignupLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $org = $request->attributes->get('currentOrganization');

        if ($org instanceof Organization && $org->usesParentSignup()) {
            SignupLocale::apply($request);

            return $next($request);
        }

        if (! $org instanceof Organization) {
            SignupLocale::apply($request);

            return $next($request);
        }

        if ($request->session()->has('signup_locale')) {
            $locale = SignupLocale::resolve($request);
            $request->session()->put('signup_locale', $locale);
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
