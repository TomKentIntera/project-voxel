<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LocationsCacheController extends Controller
{
    public function index(): JsonResponse
    {
        $disk = (string) config('services.locations_cache.disk', 'locations_cache');
        $path = $this->locationsCachePath();
        $payload = $this->readPayload($disk, $path);

        return response()->json([
            'data' => $payload,
            'meta' => [
                'disk' => $disk,
                'path' => $path,
                'location_count' => count($payload['locations'] ?? []),
                'node_count' => count($payload['nodes'] ?? []),
            ],
        ]);
    }

    public function raw(): JsonResponse
    {
        $disk = (string) config('services.locations_cache.disk', 'locations_cache');
        $path = $this->locationsCachePath();

        return response()->json($this->readPayload($disk, $path));
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayload(string $disk, string $path): array
    {
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

