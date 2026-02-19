<?php

declare(strict_types=1);

namespace Interadigital\CoreAuth;

use Illuminate\Support\ServiceProvider;
use Interadigital\CoreAuth\Console\Commands\PurgeExpiredAuthTokens;

class CoreAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/jwt.php', 'jwt');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PurgeExpiredAuthTokens::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/jwt.php' => config_path('jwt.php'),
            ], 'core-auth-config');
        }
    }
}

