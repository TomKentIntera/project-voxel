<?php

declare(strict_types=1);

namespace App\Services\Destination;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Interadigital\CoreModels\Models\Node;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\TelemetryNode;
use Interadigital\CoreModels\Models\TelemetryServer;
use InvalidArgumentException;
use Throwable;

class ServerDestinationOrchestratorService
{
    /**
     * @return array{
     *   plan: array{name: string, required_ram_mb: int, required_ram_gb: int},
     *   filters: array{
     *     requested_region: string|null,
     *     effective_regions: list<string>,
     *     server_identifier: string|null,
     *     excluded_node_id: string|null
     *   },
     *   destination: array<string, mixed>|null,
     *   candidates: list<array<string, mixed>>
     * }
     */
    public function resolve(string $planName, ?string $region = null, ?string $serverIdentifier = null): array
    {
        $plan = $this->resolvePlan($planName);
        $requiredRamGb = max(0, (int) ($plan['ram'] ?? 0));
        $requiredRamMb = $requiredRamGb * 1024;

        $requestedRegion = $this->normalizeOptionalString($region);
        $allowedPlanRegions = $this->resolvePlanRegions($plan);
        $shouldFilterByRegion = false;
        $effectiveRegions = [];

        if ($requestedRegion !== null) {
            $shouldFilterByRegion = true;
            $effectiveRegions = $allowedPlanRegions !== [] && ! in_array($requestedRegion, $allowedPlanRegions, true)
                ? []
                : [$requestedRegion];
        } elseif ($allowedPlanRegions !== []) {
            $shouldFilterByRegion = true;
            $effectiveRegions = $allowedPlanRegions;
        }

        $normalizedServerIdentifier = $this->normalizeOptionalString($serverIdentifier);
        $excludedNodeId = null;

        if ($normalizedServerIdentifier !== null) {
            $server = $this->resolveServerByIdentifier($normalizedServerIdentifier);

            if (! ($server instanceof Server)) {
                throw new InvalidArgumentException('The provided server_id does not match an existing server.');
            }

            $excludedNodeId = $this->resolveCurrentNodeId($server);
        }

        $candidateNodesQuery = Node::query();

        if ($shouldFilterByRegion) {
            $candidateNodesQuery->whereIn('region', $effectiveRegions);
        }

        if (is_string($excludedNodeId) && trim($excludedNodeId) !== '') {
            $candidateNodesQuery->where('id', '!=', $excludedNodeId);
        }

        $candidateNodes = $candidateNodesQuery->get();
        $nodeIds = $candidateNodes->pluck('id')->all();
        $cpuByNodeId = $this->cpuAveragesByNodeId($nodeIds);
        $ramAvailability = $this->ramAvailabilityFromLocationsCache();

        $candidates = [];

        foreach ($candidateNodes as $node) {
            $availableRamMb = $this->resolveAvailableRamForNode($node, $ramAvailability);

            if ($availableRamMb < $requiredRamMb) {
                continue;
            }

            $cpu = $cpuByNodeId[$node->id] ?? null;
            $averageCpuPct = is_array($cpu) ? $cpu['average_cpu_pct_24h'] : null;
            $cpuSamples = is_array($cpu) ? $cpu['samples_24h'] : 0;
            $lastActiveTimestamp = $node->last_active_at instanceof Carbon
                ? $node->last_active_at->getTimestamp()
                : PHP_INT_MIN;
            $lastUsedTimestamp = $node->last_used_at instanceof Carbon
                ? $node->last_used_at->getTimestamp()
                : PHP_INT_MIN;

            $candidates[] = [
                'id' => $node->id,
                'name' => $node->name,
                'region' => $node->region,
                'ip_address' => $node->ip_address,
                'available_ram_mb' => $availableRamMb,
                'available_ram_gb' => round($availableRamMb / 1024, 2),
                'average_cpu_pct_24h' => $averageCpuPct,
                'cpu_samples_24h' => $cpuSamples,
                'last_active_at' => $node->last_active_at?->toIso8601String(),
                'last_used_at' => $node->last_used_at?->toIso8601String(),
                'sort_cpu_pct' => $averageCpuPct ?? 101.0,
                'sort_last_active_timestamp' => $lastActiveTimestamp,
                'sort_last_used_timestamp' => $lastUsedTimestamp,
            ];
        }

        usort($candidates, function (array $left, array $right): int {
            $cpuComparison = ($left['sort_cpu_pct'] <=> $right['sort_cpu_pct']);
            if ($cpuComparison !== 0) {
                return $cpuComparison;
            }

            $ramComparison = ($right['available_ram_mb'] <=> $left['available_ram_mb']);
            if ($ramComparison !== 0) {
                return $ramComparison;
            }

            $samplesComparison = ($right['cpu_samples_24h'] <=> $left['cpu_samples_24h']);
            if ($samplesComparison !== 0) {
                return $samplesComparison;
            }

            $lastActiveComparison = ($right['sort_last_active_timestamp'] <=> $left['sort_last_active_timestamp']);
            if ($lastActiveComparison !== 0) {
                return $lastActiveComparison;
            }

            $lastUsedComparison = ($right['sort_last_used_timestamp'] <=> $left['sort_last_used_timestamp']);
            if ($lastUsedComparison !== 0) {
                return $lastUsedComparison;
            }

            return strcmp((string) $left['id'], (string) $right['id']);
        });

        $rankedCandidates = array_map(function (array $candidate): array {
            unset($candidate['sort_cpu_pct'], $candidate['sort_last_active_timestamp'], $candidate['sort_last_used_timestamp']);

            return $candidate;
        }, $candidates);

        return [
            'plan' => [
                'name' => $planName,
                'required_ram_mb' => $requiredRamMb,
                'required_ram_gb' => $requiredRamGb,
            ],
            'filters' => [
                'requested_region' => $requestedRegion,
                'effective_regions' => $effectiveRegions,
                'server_identifier' => $normalizedServerIdentifier,
                'excluded_node_id' => $excludedNodeId,
            ],
            'destination' => $rankedCandidates[0] ?? null,
            'candidates' => array_values($rankedCandidates),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvePlan(string $planName): array
    {
        $plans = config('plans.planList', []);

        if (! is_array($plans)) {
            throw new InvalidArgumentException('Plan configuration is invalid.');
        }

        foreach ($plans as $plan) {
            if (! is_array($plan)) {
                continue;
            }

            if (($plan['name'] ?? null) === $planName) {
                return $plan;
            }
        }

        throw new InvalidArgumentException('The selected plan is not configured.');
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return list<string>
     */
    private function resolvePlanRegions(array $plan): array
    {
        $regions = [];

        $planLocations = $plan['locations'] ?? [];
        if (! is_array($planLocations)) {
            return [];
        }

        foreach ($planLocations as $locationKey) {
            if (! is_string($locationKey)) {
                continue;
            }

            $trimmedLocationKey = trim($locationKey);
            if ($trimmedLocationKey === '') {
                continue;
            }

            $pteroLocation = config("plans.locations.{$trimmedLocationKey}.ptero_location");

            if (is_string($pteroLocation) && trim($pteroLocation) !== '') {
                $regions[] = trim($pteroLocation);
                continue;
            }

            if (str_contains($trimmedLocationKey, '.')) {
                $regions[] = $trimmedLocationKey;
            }
        }

        return array_values(array_unique($regions));
    }

    private function resolveServerByIdentifier(string $serverIdentifier): ?Server
    {
        $query = Server::query()->where('uuid', $serverIdentifier);

        if (ctype_digit($serverIdentifier)) {
            $query->orWhere('id', (int) $serverIdentifier);
        }

        return $query->first();
    }

    private function resolveCurrentNodeId(Server $server): ?string
    {
        $identifiers = [(string) $server->id];

        if (is_string($server->uuid) && trim($server->uuid) !== '') {
            $identifiers[] = trim($server->uuid);
        }

        $telemetryServer = TelemetryServer::query()
            ->whereIn('server_id', array_values(array_unique($identifiers)))
            ->orderByDesc('created_at')
            ->first();

        if ($telemetryServer instanceof TelemetryServer) {
            $nodeId = trim((string) $telemetryServer->node_id);

            if ($nodeId !== '') {
                return $nodeId;
            }
        }

        $config = $this->decodeServerConfig($server->config);

        $nodeIdCandidates = [
            $config['node_id'] ?? null,
            $config['current_node_id'] ?? null,
            $config['ptero_node_id'] ?? null,
            $config['nodeId'] ?? null,
        ];

        foreach ($nodeIdCandidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }

            if (is_int($candidate)) {
                return (string) $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<string, array{average_cpu_pct_24h: float|null, samples_24h: int}>
     */
    private function cpuAveragesByNodeId(array $nodeIds): array
    {
        if ($nodeIds === []) {
            return [];
        }

        $rows = TelemetryNode::query()
            ->selectRaw('node_id, AVG(cpu_pct) as average_cpu_pct_24h, COUNT(*) as samples_24h')
            ->whereIn('node_id', $nodeIds)
            ->where('created_at', '>=', now()->subDay())
            ->groupBy('node_id')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $nodeId = trim((string) $row->node_id);
            if ($nodeId === '') {
                continue;
            }

            $averageCpu = is_numeric($row->average_cpu_pct_24h)
                ? round((float) $row->average_cpu_pct_24h, 3)
                : null;

            $map[$nodeId] = [
                'average_cpu_pct_24h' => $averageCpu,
                'samples_24h' => max(0, (int) $row->samples_24h),
            ];
        }

        return $map;
    }

    /**
     * @return array{
     *   by_lookup_key: array<string, int>,
     *   max_by_location: array<string, int>
     * }
     */
    private function ramAvailabilityFromLocationsCache(): array
    {
        $payload = $this->readLocationsPayload();
        $byLookupKey = [];
        $maxByLocation = [];

        $locations = $payload['locations'] ?? [];
        if (is_array($locations)) {
            foreach ($locations as $location) {
                if (! is_array($location)) {
                    continue;
                }

                $locationShortCode = $this->normalizeLookupValue($location['short'] ?? null);

                if ($locationShortCode === null) {
                    continue;
                }

                $maxByLocation[$locationShortCode] = max(
                    $maxByLocation[$locationShortCode] ?? 0,
                    max(0, (int) ($location['maxFreeMemory'] ?? 0)),
                );
            }
        }

        $nodes = $payload['nodes'] ?? [];

        if (! is_array($nodes)) {
            return [
                'by_lookup_key' => $byLookupKey,
                'max_by_location' => $maxByLocation,
            ];
        }

        foreach ($nodes as $cacheNode) {
            if (! is_array($cacheNode)) {
                continue;
            }

            $availableRamMb = max(0, (int) ($cacheNode['memoryFree'] ?? 0));

            $lookupKeys = [
                $this->normalizeLookupValue($cacheNode['id'] ?? null),
                $this->normalizeLookupValue($cacheNode['name'] ?? null),
                $this->normalizeLookupValue($cacheNode['fqdn'] ?? null),
            ];

            foreach ($lookupKeys as $lookupKey) {
                if ($lookupKey === null) {
                    continue;
                }

                $byLookupKey[$lookupKey] = max($byLookupKey[$lookupKey] ?? 0, $availableRamMb);
            }
        }

        return [
            'by_lookup_key' => $byLookupKey,
            'max_by_location' => $maxByLocation,
        ];
    }

    /**
     * @param array{
     *   by_lookup_key: array<string, int>,
     *   max_by_location: array<string, int>
     * } $ramAvailability
     */
    private function resolveAvailableRamForNode(Node $node, array $ramAvailability): int
    {
        $byLookupKey = $ramAvailability['by_lookup_key'] ?? [];
        $lookupCandidates = [
            $this->normalizeLookupValue($node->id),
            $this->normalizeLookupValue($node->name),
            $this->normalizeLookupValue($node->ip_address),
        ];

        $bestMatch = null;

        foreach ($lookupCandidates as $lookupCandidate) {
            if ($lookupCandidate === null) {
                continue;
            }

            if (array_key_exists($lookupCandidate, $byLookupKey)) {
                $bestMatch = max($bestMatch ?? 0, (int) $byLookupKey[$lookupCandidate]);
            }
        }

        if ($bestMatch !== null) {
            return $bestMatch;
        }

        $regionKey = $this->normalizeLookupValue($node->region);

        if ($regionKey !== null) {
            $maxByLocation = $ramAvailability['max_by_location'] ?? [];

            if (array_key_exists($regionKey, $maxByLocation)) {
                return max(0, (int) $maxByLocation[$regionKey]);
            }
        }

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function readLocationsPayload(): array
    {
        $localPath = storage_path('app/locations.json');
        $decoded = null;

        try {
            if (File::exists($localPath)) {
                $decoded = json_decode((string) File::get($localPath), true);
            }
        } catch (Throwable) {
            $decoded = null;
        }

        if (is_array($decoded)) {
            return $decoded;
        }

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

            $remoteDecoded = json_decode((string) $storage->get($path), true);

            return is_array($remoteDecoded) ? $remoteDecoded : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeServerConfig(?string $config): array
    {
        if (! is_string($config) || trim($config) === '') {
            return [];
        }

        $decoded = json_decode($config, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeLookupValue(mixed $value): ?string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : strtolower($trimmed);
    }
}
