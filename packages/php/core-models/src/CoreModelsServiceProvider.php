<?php

declare(strict_types=1);

namespace Interadigital\CoreModels;

use Illuminate\Support\ServiceProvider;

class CoreModelsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
