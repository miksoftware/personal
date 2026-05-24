<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS for all generated URLs when running in production
        // (needed when SSL is terminated at the reverse proxy/nginx level)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
