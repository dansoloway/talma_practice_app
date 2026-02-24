<?php

namespace App\Providers;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('organization', function (string $value) {
            return Organization::where('slug', $value)->where('is_active', true)->firstOrFail();
        });

        Route::bind('classroom', function (string $value, $route) {
            $org = $route->parameter('organization');
            if ($org instanceof Organization) {
                return $org->classes()->where('slug', $value)->firstOrFail();
            }
            return \App\Models\Classroom::where('slug', $value)->firstOrFail();
        });

        View::composer('layouts.admin', function ($view) {
            $accessibleOrgs = collect();
            $user = Auth::guard('admin')->user();
            if ($user) {
                if ($user->role === 'admin') {
                    $accessibleOrgs = Organization::where('is_active', true)->orderBy('name')->get();
                } else {
                    $accessibleOrgs = $user->organizations()
                        ->where('organizations.is_active', true)
                        ->wherePivotIn('role', ['org_admin', 'teacher'])
                        ->orderBy('organizations.name')
                        ->get();
                }
            }
            $view->with('accessibleOrgs', $accessibleOrgs);
        });
    }
}

