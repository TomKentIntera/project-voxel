<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application;

use App\Services\ReferralCode\Service as ReferralService;
use App\Services\Log\Service as LoggerService;
use App\Services\Stripe\Service as StripeService;

class ReferralProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('referral', function (Application $app) {
            $logger = new LoggerService();
            $stripe = new StripeService($logger);
            return new ReferralService($logger, $stripe);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
