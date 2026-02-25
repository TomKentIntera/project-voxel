<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Metrics\ResourceConsumptionCacheService;
use Illuminate\Console\Command;

class CacheResourceConsumptionMetricsCommand extends Command
{
    protected $signature = 'metrics:cache-resource-consumption';

    protected $description = 'Cache overall resource consumption for dashboard';

    public function handle(ResourceConsumptionCacheService $service): int
    {
        $value = $service->refreshLastHourConsumptionPercent();

        $this->info(sprintf('Cached last-hour resource consumption at %.2f%%.', $value));

        return self::SUCCESS;
    }
}

