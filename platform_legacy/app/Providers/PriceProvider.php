<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application;

use App\Services\Price\Service as PriceService;
use App\Services\Log\Service as LoggerService;

class PriceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('price', function (Application $app) {
            $logger = new LoggerService();
            return new PriceService($logger);
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
