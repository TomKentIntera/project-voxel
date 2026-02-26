<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Pterodactyl\Services\PterodactylApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UpdateLocationFreeSpaceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  PterodactylApiClient  $pterodactylApiClient
     */
    public function handle(PterodactylApiClient $pterodactylApiClient): void
    {
        $locations = $pterodactylApiClient->listLocations(includeNodes: true);
        $payload = $this->buildPayload($locations);
        $encodedPayload = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (! is_string($encodedPayload)) {
            throw new RuntimeException('Unable to encode Pterodactyl locations payload.');
        }

        // Keep the existing local file for orchestrator consumers.
        $localPath = storage_path('app/locations.json');
        File::ensureDirectoryExists(dirname($localPath));
        $bytesWritten = File::put($localPath, $encodedPayload);

        if ($bytesWritten === false) {
            throw new RuntimeException('Unable to write local locations cache file.');
        }

        // Publish to shared object storage for cross-service consumption.
        Storage::disk($this->locationsCacheDisk())->put($this->locationsCachePath(), $encodedPayload);
    }

    /**
     * @param  list<array<string, mixed>>  $locations
     * @return array{
     *     locations: list<array<string, int|float|string>>,
     *     nodes: list<array<string, int|float|string>>
     * }
     */
    private function buildPayload(array $locations): array
    {
        $output = [
            'locations' => [],
            'nodes' => [],
        ];

        foreach ($locations as $location) {
            $locationShortCode = (string) ($location['short'] ?? '');

            $entry = [
                'id' => (int) ($location['id'] ?? 0),
                'short' => $locationShortCode,
                'long' => (string) ($location['long'] ?? ''),
                'nodeCount' => 0,
            ];

            $totalMemory = 0;
            $totalUsedMemory = 0;
            $totalFreeMemory = 0;
            $maxFreeMemory = 0;
            $memoryUsedFreestNodePercent = 100.0;

            foreach ($this->extractNodes($location) as $nodeData) {
                $memory = max(0, (int) ($nodeData['memory'] ?? 0));
                $memoryAllocated = max(0, (int) data_get($nodeData, 'allocated_resources.memory', 0));
                $memoryFree = max(0, $memory - $memoryAllocated);
                $memoryUsedPercent = $memory > 0 ? ($memoryAllocated / $memory) * 100 : 0.0;

                $nodeEntry = [
                    'id' => (int) ($nodeData['id'] ?? 0),
                    'name' => (string) ($nodeData['name'] ?? ''),
                    'fqdn' => (string) ($nodeData['fqdn'] ?? ''),
                    'memory' => $memory,
                    'location' => $locationShortCode,
                    'memoryAllocated' => $memoryAllocated,
                    'memoryUsedPercent' => $memoryUsedPercent,
                    'memoryFree' => $memoryFree,
                ];

                $output['nodes'][] = $nodeEntry;
                $entry['nodeCount']++;

                $totalMemory += $memory;
                $totalUsedMemory += $memoryAllocated;
                $totalFreeMemory += $memoryFree;

                if ($memoryFree > $maxFreeMemory) {
                    $maxFreeMemory = $memoryFree;
                }

                if ($memoryUsedPercent < $memoryUsedFreestNodePercent) {
                    $memoryUsedFreestNodePercent = $memoryUsedPercent;
                }
            }

            $totalMemoryUsedPercent = $totalMemory > 0 ? ($totalUsedMemory / $totalMemory) * 100 : 0.0;

            $entry['totalMemory'] = $totalMemory;
            $entry['totalUsedMemory'] = $totalUsedMemory;
            $entry['totalFreeMemory'] = $totalFreeMemory;
            $entry['totalMemoryUsedPercent'] = $totalMemoryUsedPercent;
            $entry['maxFreeMemory'] = $maxFreeMemory;
            $entry['memoryUsedFreestNodePercent'] = $memoryUsedFreestNodePercent;
            $entry['totalMemoryGB'] = $totalMemory / 1024;
            $entry['totalUsedMemoryGB'] = $totalUsedMemory / 1024;
            $entry['totalFreeMemoryGB'] = $totalFreeMemory / 1024;
            $entry['maxFreeMemoryGB'] = $maxFreeMemory / 1024;

            $output['locations'][] = $entry;
        }

        return $output;
    }

    /**
     * @param  array<string, mixed>  $location
     * @return list<array<string, mixed>>
     */
    private function extractNodes(array $location): array
    {
        $nodes = $location['relationships']['nodes']['data'] ?? null;

        if (! is_array($nodes)) {
            return [];
        }

        $result = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $attributes = $node['attributes'] ?? null;
            $normalizedNode = is_array($attributes) ? $attributes : $node;

            if (! is_array($normalizedNode)) {
                continue;
            }

            $result[] = $normalizedNode;
        }

        return $result;
    }

    private function locationsCacheDisk(): string
    {
        return (string) config('services.locations_cache.disk', 'locations_cache');
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
