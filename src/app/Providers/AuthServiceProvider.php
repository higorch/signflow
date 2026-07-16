<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::define('root', function ($user) {
            return $user->role === 'root';
        });

        Gate::define('admin', function ($user) {
            return $user->role === 'admin';
        });

        Gate::define('customer', function ($user) {
            return $user->role === 'customer';
        });

        Gate::define('signer', function ($user) {
            return $user->role === 'signer';
        });
    }
}
