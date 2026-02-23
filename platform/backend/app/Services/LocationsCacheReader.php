<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LocationsCacheReader
{
    public function __construct(
        private readonly CacheRepository $cache
    ) {}

    /**
     * Build a map of pterodactyl location short code => max free memory (MB).
     *
     * @return array<string, int>
     */
    public function maxFreeMemoryByLocationShortCode(): array
    {
        $map = [];

        foreach ($this->cachedPayload()['locations'] ?? [] as $location) {
            if (! is_array($location)) {
                continue;
            }

            $shortCode = trim((string) ($location['short'] ?? ''));

            if ($shortCode === '') {
                continue;
            }

            $map[$shortCode] = (int) ($location['maxFreeMemory'] ?? 0);
        }

        return $map;
    }

    /**
     * @return array<string, mixed>
     */
    private function cachedPayload(): array
    {
        $cacheTtlSeconds = max(1, (int) config('services.locations_cache.ttl_seconds', 60));

        return $this->cache->remember(
            $this->cacheKey(),
            now()->addSeconds($cacheTtlSeconds),
            fn (): array => $this->readPayloadFromStorage(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayloadFromStorage(): array
    {
        $disk = (string) config('services.locations_cache.disk', 'locations_cache');
        $path = trim((string) config('services.locations_cache.path', 'locations.json'));

        if ($path === '') {
            return [];
        }

        try {
            $storage = Storage::disk($disk);

            if (! $storage->exists($path)) {
                return [];
            }

            $decoded = json_decode((string) $storage->get($path), true);
        } catch (Throwable) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function cacheKey(): string
    {
        $disk = (string) config('services.locations_cache.disk', 'locations_cache');
        $path = trim((string) config('services.locations_cache.path', 'locations.json'));

        return sprintf('plans.locations-cache.%s.%s', $disk, md5($path));
    }
}
