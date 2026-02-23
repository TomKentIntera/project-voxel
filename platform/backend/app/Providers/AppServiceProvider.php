<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Interadigital\CoreNotifications\Transport\SlackTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SlackTransport::class, function (): SlackTransport {
            return new SlackTransport(
                (string) config('services.slack.notifications.bot_user_oauth_token', ''),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
