<?php

namespace App\Providers;

use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;
use Interadigital\CoreNotifications\Transport\SlackTransport;
use Interadigital\CoreModels\Models\User;

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
        User::observe(UserObserver::class);
    }
}
