<?php

declare(strict_types=1);

namespace App\Services\Metrics;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Interadigital\CoreModels\Models\TelemetryNode;

class ResourceConsumptionCacheService
{
    public function getLastHourConsumptionPercent(): float
    {
        try {
            $cached = $this->cacheStore()->get($this->cacheKey());

            if (is_numeric($cached)) {
                return (float) $cached;
            }
        } catch (\Throwable) {
            // If Redis is temporarily unavailable, fall back to fresh computation.
        }

        return $this->refreshLastHourConsumptionPercent();
    }

    public function refreshLastHourConsumptionPercent(): float
    {
        $value = $this->calculateLastHourConsumptionPercent();

        try {
            $this->cacheStore()->put(
                $this->cacheKey(),
                $value,
                now()->addMinutes($this->cacheTtlMinutes()),
            );
        } catch (\Throwable) {
            // Keep the dashboard metric available even if cache writes fail.
        }

        return $value;
    }

    private function calculateLastHourConsumptionPercent(): float
    {
        $average = TelemetryNode::query()
            ->where('created_at', '>=', now()->subHour())
            ->avg('cpu_pct');

        if (! is_numeric($average)) {
            return 0.0;
        }

        $bounded = min(max((float) $average, 0.0), 100.0);

        return round($bounded, 2);
    }

    private function cacheStore(): CacheRepository
    {
        return Cache::store($this->cacheStoreName());
    }

    private function cacheStoreName(): string
    {
        return (string) config('metrics.resource_consumption.cache_store', 'redis');
    }

    private function cacheKey(): string
    {
        return (string) config('metrics.resource_consumption.cache_key', 'metrics:resource-consumption:last-hour');
    }

    private function cacheTtlMinutes(): int
    {
        return max((int) config('metrics.resource_consumption.cache_ttl_minutes', 15), 1);
    }
}
