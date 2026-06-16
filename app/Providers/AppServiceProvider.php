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
            return Organization::where('slug', $value)->firstOrFail();
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
                    // Global admins see all orgs including Root; Root first
                    $root = Organization::root();
                    $tenantOrgs = Organization::where('is_active', true)->where('is_root', false)->orderBy('name')->get();
                    $accessibleOrgs = $root ? collect([$root])->merge($tenantOrgs) : $tenantOrgs;
                } else {
                    // Non-global-admins never see Root
                    $accessibleOrgs = $user->organizations()
                        ->where('organizations.is_active', true)
                        ->where('organizations.is_root', false)
                        ->wherePivotIn('role', ['org_admin', 'teacher'])
                        ->orderBy('organizations.name')
                        ->get();
                }
            }
            $view->with('accessibleOrgs', $accessibleOrgs);
        });
    }
}

