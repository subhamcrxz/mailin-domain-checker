<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('domain-checks', function () {
            return Limit::perMinute(
                (int) env(
                    'DOMAIN_CHECK_RATE_LIMIT',
                    60
                )
            );
        });
    }
}