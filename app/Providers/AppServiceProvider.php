<?php

namespace App\Providers;

use App\Models\Organization;
use Illuminate\Support\Facades\Route;
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
    }
}

