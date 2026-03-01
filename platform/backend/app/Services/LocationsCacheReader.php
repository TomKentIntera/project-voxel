<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;
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

        foreach ($this->locations() as $location) {
            $map[$location['short']] = $location['maxFreeMemory'];
        }

        return $map;
    }

    /**
     * Return normalized location rows from the shared locations cache payload.
     *
     * @return list<array{short: string, long: string, maxFreeMemory: int}>
     */
    public function locations(): array
    {
        $rows = [];

        foreach ($this->cachedPayload()['locations'] ?? [] as $location) {
            if (! is_array($location)) {
                continue;
            }

            $shortCode = trim((string) ($location['short'] ?? ''));

            if ($shortCode === '') {
                continue;
            }

            $rows[] = [
                'short' => $shortCode,
                'long' => trim((string) ($location['long'] ?? '')),
                'maxFreeMemory' => (int) ($location['maxFreeMemory'] ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function cachedPayload(): array
    {
        $cacheTtlSeconds = max(1, (int) config('services.locations_cache.ttl_seconds', 60));
        $cacheKey = $this->payloadCacheKey();
        $freshPayload = $this->readPayloadFromStorage();
        if ($freshPayload !== []) {
            $this->cache->put($cacheKey, $freshPayload, now()->addSeconds($cacheTtlSeconds));

            return $freshPayload;
        }

        // Storage can be temporarily unavailable. Fall back to the last known
        // non-empty payload so plans/availability remain stable.
        $cached = $this->cache->get($cacheKey);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        // Do not cache empty payloads; we want to recover quickly once data appears in shared storage.
        $this->cache->forget($cacheKey);

        return [];
    }

    public function forgetCachedPayload(): void
    {
        $this->cache->forget($this->payloadCacheKey());
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayloadFromStorage(): array
    {
        $disk = (string) config('services.locations_cache.disk', 'locations_cache');
        $path = $this->locationsCachePath();

        if ($path === '') {
            return [];
        }

        try {
            $storage = Storage::disk($disk);

            if (! $storage->exists($path)) {
                return [];
            }

            $decoded = json_decode((string) $storage->get($path), true);
        } catch (Throwable $exception) {
            Log::warning('Failed to read locations cache payload.', [
                'disk' => $disk,
                'path' => $path,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function payloadCacheKey(): string
    {
        $disk = (string) config('services.locations_cache.disk', 'locations_cache');
        $path = $this->locationsCachePath();

        return sprintf('plans.locations-cache.%s.%s', $disk, md5($path));
    }

    private function locationsCachePath(): string
    {
        $configuredPath = trim((string) config('services.locations_cache.path', 'locations.json'));

        if ($configuredPath === '') {
            return 'locations.json';
        }

        $normalizedPath = str_replace('\\', '/', $configuredPath);
        $storageAppMarker = '/storage/app/';

        if (str_contains($normalizedPath, $storageAppMarker)) {
            $normalizedPath = (string) substr(
                $normalizedPath,
                strpos($normalizedPath, $storageAppMarker) + strlen($storageAppMarker)
            );
        } else {
            $normalizedPath = ltrim($normalizedPath, '/');
        }

        return $normalizedPath !== '' ? $normalizedPath : 'locations.json';
    }
}
