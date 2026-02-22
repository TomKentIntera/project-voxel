<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\ServerEvent;
use Interadigital\CoreModels\Models\TelemetryServer;
use Interadigital\CoreModels\Models\TelemetryServerSample;
use Interadigital\CoreModels\Models\User;

class ServerController extends Controller
{
    /**
     * Paginated, searchable server listing for administrators.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Server::query()->with('user')->withCount('events');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('plan', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        $planMap = collect(config('plans.planList', []))->keyBy('name');

        $items = collect($paginator->items())->map(function (Server $server) use ($planMap): array {
            $config = $this->decodeConfig($server->config);
            $plan = $planMap->get($server->plan);
            return [
                'id' => $server->id,
                'uuid' => $server->uuid,
                'name' => $this->resolveServerName($config),
                'created_at' => $server->created_at?->toIso8601String(),
                'suspended' => (bool) $server->suspended,
                'status' => $server->status,
                'plan' => $server->plan,
                'plan_title' => is_array($plan) ? ($plan['title'] ?? null) : null,
                'plan_ram' => is_array($plan) ? ($plan['ram'] ?? null) : null,
                'events_count' => (int) ($server->events_count ?? 0),
                'owner' => $this->transformUser($server->user),
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Return a single server profile with owner and timeline events.
     */
    public function show(int $id): JsonResponse
    {
        $server = Server::with(['user'])->withCount('events')->find($id);

        if ($server === null) {
            return response()->json(['message' => 'Server not found.'], 404);
        }

        $planMap = collect(config('plans.planList', []))->keyBy('name');
        $plan = $planMap->get($server->plan);
        $config = $this->decodeConfig($server->config);

        $events = $server->events()
            ->with('actor')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (ServerEvent $event): array => [
                'id' => $event->id,
                'type' => $event->type,
                'label' => $this->labelEventType($event->type),
                'actor' => $this->transformUser($event->actor),
                'meta' => is_array($event->meta) ? $event->meta : [],
                'created_at' => $event->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => [
                'id' => $server->id,
                'uuid' => $server->uuid,
                'name' => $this->resolveServerName($config),
                'created_at' => $server->created_at?->toIso8601String(),
                'suspended' => (bool) $server->suspended,
                'status' => $server->status,
                'initialised' => (bool) $server->initialised,
                'ptero_id' => $server->ptero_id,
                'plan' => $server->plan,
                'plan_title' => is_array($plan) ? ($plan['title'] ?? null) : null,
                'plan_ram' => is_array($plan) ? ($plan['ram'] ?? null) : null,
                'events_count' => (int) ($server->events_count ?? 0),
                'owner' => $this->transformUser($server->user),
                'events' => $events,
                'performance_last_24h' => $this->buildPerformanceWindow($server),
            ],
        ]);
    }

    /**
     * @return array{
     *   from: string,
     *   to: string,
     *   latest: array{
     *     players_online: int|null,
     *     cpu_pct: float|null,
     *     io_write_bytes_per_s: float|null,
     *     node_id: string|null,
     *     recorded_at: string|null
     *   },
     *   averages: array{
     *     players_online: float|null,
     *     cpu_pct: float|null,
     *     io_write_bytes_per_s: float|null
     *   },
     *   samples: list<array{
     *     recorded_at: string,
     *     players_online: float|null,
     *     cpu_pct: float,
     *     io_write_bytes_per_s: float
     *   }>
     * }
     */
    private function buildPerformanceWindow(Server $server): array
    {
        $windowStart = now()->subDay();
        $windowEnd = now();
        $identifiers = $this->resolveTelemetryServerIdentifiers($server);

        /** @var TelemetryServer|null $latestTelemetry */
        $latestTelemetry = TelemetryServer::query()
            ->whereIn('server_id', $identifiers)
            ->orderByDesc('updated_at')
            ->first();

        $rawSamples = TelemetryServerSample::query()
            ->whereIn('server_id', $identifiers)
            ->where('recorded_at', '>=', $windowStart)
            ->orderBy('recorded_at')
            ->get();

        /**
         * @var array<string, array{
         *   recorded_at: string,
         *   players_total: float,
         *   players_count: int,
         *   cpu_total: float,
         *   io_write_total: float,
         *   count: int
         * }>
         */
        $bucketed = [];

        foreach ($rawSamples as $sample) {
            if (! ($sample->recorded_at instanceof Carbon)) {
                continue;
            }

            $bucketStart = $sample->recorded_at->copy()->second(0);
            $bucketMinute = (int) floor($bucketStart->minute / 5) * 5;
            $bucketStart->minute($bucketMinute);

            $bucketKey = $bucketStart->toIso8601String();

            if (! isset($bucketed[$bucketKey])) {
                $bucketed[$bucketKey] = [
                    'recorded_at' => $bucketKey,
                    'players_total' => 0.0,
                    'players_count' => 0,
                    'cpu_total' => 0.0,
                    'io_write_total' => 0.0,
                    'count' => 0,
                ];
            }

            if ($sample->players_online !== null) {
                $bucketed[$bucketKey]['players_total'] += (float) $sample->players_online;
                $bucketed[$bucketKey]['players_count']++;
            }

            $bucketed[$bucketKey]['cpu_total'] += (float) $sample->cpu_pct;
            $bucketed[$bucketKey]['io_write_total'] += (float) $sample->io_write_bytes_per_s;
            $bucketed[$bucketKey]['count']++;
        }

        ksort($bucketed);

        $aggregatedSamples = array_map(
            static fn (array $bucket): array => [
                'recorded_at' => $bucket['recorded_at'],
                'players_online' => $bucket['players_count'] > 0
                    ? round($bucket['players_total'] / $bucket['players_count'], 2)
                    : null,
                'cpu_pct' => round($bucket['cpu_total'] / max($bucket['count'], 1), 3),
                'io_write_bytes_per_s' => round($bucket['io_write_total'] / max($bucket['count'], 1), 3),
            ],
            array_values($bucketed),
        );

        $sampleCount = count($aggregatedSamples);

        $averageCpu = $sampleCount > 0
            ? round(array_sum(array_column($aggregatedSamples, 'cpu_pct')) / $sampleCount, 3)
            : null;

        $averageIoWrite = $sampleCount > 0
            ? round(array_sum(array_column($aggregatedSamples, 'io_write_bytes_per_s')) / $sampleCount, 3)
            : null;

        $playerSamples = array_values(array_filter(
            array_column($aggregatedSamples, 'players_online'),
            static fn (mixed $value): bool => is_float($value) || is_int($value),
        ));

        $averagePlayers = $playerSamples !== []
            ? round(array_sum($playerSamples) / count($playerSamples), 2)
            : null;

        return [
            'from' => $windowStart->toIso8601String(),
            'to' => $windowEnd->toIso8601String(),
            'latest' => [
                'players_online' => $latestTelemetry?->players_online,
                'cpu_pct' => $latestTelemetry?->cpu_pct !== null
                    ? (float) $latestTelemetry->cpu_pct
                    : null,
                'io_write_bytes_per_s' => $latestTelemetry?->io_write_bytes_per_s !== null
                    ? (float) $latestTelemetry->io_write_bytes_per_s
                    : null,
                'node_id' => $latestTelemetry?->node_id,
                'recorded_at' => $latestTelemetry?->updated_at?->toIso8601String(),
            ],
            'averages' => [
                'players_online' => $averagePlayers,
                'cpu_pct' => $averageCpu,
                'io_write_bytes_per_s' => $averageIoWrite,
            ],
            'samples' => $aggregatedSamples,
        ];
    }

    /**
     * @return list<string>
     */
    private function resolveTelemetryServerIdentifiers(Server $server): array
    {
        $identifiers = [(string) $server->id];

        if (is_string($server->uuid) && trim($server->uuid) !== '') {
            $identifiers[] = $server->uuid;
        }

        return array_values(array_unique($identifiers));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeConfig(?string $config): array
    {
        if (! is_string($config) || $config === '') {
            return [];
        }

        $decoded = json_decode($config, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function resolveServerName(array $config): string
    {
        $name = $config['name'] ?? null;

        return is_string($name) && trim($name) !== ''
            ? $name
            : 'Unnamed Server';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function transformUser(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    private function labelEventType(string $type): string
    {
        return match ($type) {
            'server.ordered.v1' => 'Server ordered',
            'server.provisioning.started' => 'Provisioning started',
            'server.provisioned' => 'Server provisioned',
            'server.cancelled' => 'Server cancelled',
            'server.suspended' => 'Server suspended',
            default => ucwords(str_replace(['.', '_', '-'], ' ', $type)),
        };
    }
}

