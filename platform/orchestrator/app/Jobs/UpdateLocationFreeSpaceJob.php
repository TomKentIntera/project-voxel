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

        Storage::disk('local')->put('locations.json', $encodedPayload);
        $this->copyToBackendStorage($encodedPayload);
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

    private function copyToBackendStorage(string $encodedPayload): void
    {
        $backendLocationsPath = $this->backendLocationsPath();

        if ($backendLocationsPath === null) {
            return;
        }

        File::ensureDirectoryExists(dirname($backendLocationsPath));
        $bytesWritten = File::put($backendLocationsPath, $encodedPayload);

        if ($bytesWritten === false) {
            throw new RuntimeException(sprintf('Unable to copy locations cache to backend storage path: %s', $backendLocationsPath));
        }
    }

    private function backendLocationsPath(): ?string
    {
        $configuredPath = trim((string) config('services.pterodactyl.backend_locations_cache_path', ''));

        if ($configuredPath !== '') {
            return $configuredPath;
        }

        $defaultPath = base_path('../backend/storage/app/locations.json');

        return File::isDirectory(dirname($defaultPath)) ? $defaultPath : null;
    }
}
