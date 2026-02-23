<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Services\Metrics\ResourceConsumptionCacheService;

class ResourceConsumptionLastHour extends Metric
{
    public function key(): string
    {
        return 'resource_consumption_last_hour';
    }

    public function label(): string
    {
        return 'Resource Consumption (1h)';
    }

    public function value(): float
    {
        return app(ResourceConsumptionCacheService::class)->getLastHourConsumptionPercent();
    }

    public function suffix(): ?string
    {
        return '%';
    }
}
