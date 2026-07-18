<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        class_alias(\App\Services\AuthService::class, 'AuthService');
        class_alias(\App\Services\RouteService::class, 'RouteService');
        class_alias(\App\Support\AttachmentPath::class, 'AttachmentPath');
        class_alias(\App\Support\ImageWatermark::class, 'ImageWatermark');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
