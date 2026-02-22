<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Interadigital\CoreModels\Models\Node;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\TelemetryNode;
use Interadigital\CoreModels\Models\TelemetryNodeSample;
use Interadigital\CoreModels\Models\TelemetryServer;
use Interadigital\CoreModels\Models\User;
use Symfony\Component\HttpFoundation\Response;

class NodeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Node::query();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        $items = collect($paginator->items())->map(
            fn (Node $node): array => $this->transformNode($node)
        );

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

    public function show(string $id): JsonResponse
    {
        $node = Node::find($id);

        if ($node === null) {
            return response()->json([
                'message' => 'Node not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $servers = $this->resolveNodeServers($id);
        $latestTelemetry = TelemetryNode::find($id);

        return response()->json([
            'data' => [
                ...$this->transformNode($node),
                'performance_last_24h' => $this->buildPerformanceWindow($id, $latestTelemetry),
                'servers' => $servers,
                'servers_count' => count($servers),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['nullable', 'string', 'max:255', 'unique:nodes,id'],
            'name' => ['required', 'string', 'max:255', 'unique:nodes,name'],
            'region' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
        ]);

        $rawToken = Node::generateToken();

        $node = Node::create([
            'id' => $validated['id'] ?? null,
            'name' => $validated['name'],
            'region' => $validated['region'],
            'ip_address' => $validated['ip_address'],
            'token_hash' => Node::hashToken($rawToken),
        ]);

        return response()->json([
            'data' => [
                ...$this->transformNode($node),
                'node_token' => $rawToken,
            ],
        ], Response::HTTP_CREATED);
    }

    public function destroy(string $id): JsonResponse
    {
        $node = Node::find($id);

        if ($node === null) {
            return response()->json([
                'message' => 'Node not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($id, $node): void {
            TelemetryNodeSample::query()->where('node_id', $id)->delete();
            TelemetryServer::query()->where('node_id', $id)->delete();
            TelemetryNode::query()->where('node_id', $id)->delete();
            $node->delete();
        });

        return response()->json([
            'message' => 'Node deleted.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformNode(Node $node): array
    {
        return [
            'id' => $node->id,
            'name' => $node->name,
            'region' => $node->region,
            'ip_address' => $node->ip_address,
            'last_active_at' => $node->last_active_at?->toIso8601String(),
            'last_used_at' => $node->last_used_at?->toIso8601String(),
            'created_at' => $node->created_at?->toIso8601String(),
            'updated_at' => $node->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{
     *   from: string,
     *   to: string,
     *   latest: array{cpu_pct: float|null, iowait_pct: float|null, recorded_at: string|null},
     *   averages: array{cpu_pct: float|null, iowait_pct: float|null},
     *   samples: list<array{recorded_at: string, cpu_pct: float, iowait_pct: float}>
     * }
     */
    private function buildPerformanceWindow(string $nodeId, ?TelemetryNode $latestTelemetry): array
    {
        $windowStart = now()->subDay();
        $windowEnd = now();

        $samples = TelemetryNodeSample::query()
            ->where('node_id', $nodeId)
            ->where('recorded_at', '>=', $windowStart)
            ->orderBy('recorded_at')
            ->get();

        /**
         * @var array<string, array{recorded_at: string, cpu_total: float, iowait_total: float, count: int}>
         */
        $bucketed = [];

        foreach ($samples as $sample) {
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
                    'cpu_total' => 0.0,
                    'iowait_total' => 0.0,
                    'count' => 0,
                ];
            }

            $bucketed[$bucketKey]['cpu_total'] += (float) $sample->cpu_pct;
            $bucketed[$bucketKey]['iowait_total'] += (float) $sample->iowait_pct;
            $bucketed[$bucketKey]['count']++;
        }

        ksort($bucketed);

        $aggregatedSamples = array_map(
            static fn (array $bucket): array => [
                'recorded_at' => $bucket['recorded_at'],
                'cpu_pct' => round($bucket['cpu_total'] / max($bucket['count'], 1), 3),
                'iowait_pct' => round($bucket['iowait_total'] / max($bucket['count'], 1), 3),
            ],
            array_values($bucketed),
        );

        $sampleCount = count($aggregatedSamples);

        $averageCpu = $sampleCount > 0
            ? round(array_sum(array_column($aggregatedSamples, 'cpu_pct')) / $sampleCount, 3)
            : null;

        $averageIowait = $sampleCount > 0
            ? round(array_sum(array_column($aggregatedSamples, 'iowait_pct')) / $sampleCount, 3)
            : null;

        return [
            'from' => $windowStart->toIso8601String(),
            'to' => $windowEnd->toIso8601String(),
            'latest' => [
                'cpu_pct' => $latestTelemetry?->cpu_pct !== null
                    ? (float) $latestTelemetry->cpu_pct
                    : null,
                'iowait_pct' => $latestTelemetry?->iowait_pct !== null
                    ? (float) $latestTelemetry->iowait_pct
                    : null,
                'recorded_at' => $latestTelemetry?->updated_at?->toIso8601String(),
            ],
            'averages' => [
                'cpu_pct' => $averageCpu,
                'iowait_pct' => $averageIowait,
            ],
            'samples' => $aggregatedSamples,
        ];
    }

    /**
     * @return list<array{
     *   server_id: string,
     *   players_online: int|null,
     *   cpu_pct: float,
     *   io_write_bytes_per_s: float,
     *   last_reported_at: string|null,
     *   server: array{
     *     id: int,
     *     uuid: string,
     *     name: string,
     *     status: string|null,
     *     plan: string|null,
     *     owner: array<string, mixed>|null
     *   }|null
     * }>
     */
    private function resolveNodeServers(string $nodeId): array
    {
        $telemetryServers = TelemetryServer::query()
            ->where('node_id', $nodeId)
            ->orderBy('server_id')
            ->get();

        if ($telemetryServers->isEmpty()) {
            return [];
        }

        $serversByIdentifier = $this->resolveServersByTelemetryIdentifier($telemetryServers);

        return $telemetryServers->map(function (TelemetryServer $telemetryServer) use ($serversByIdentifier): array {
            $linkedServer = $serversByIdentifier->get($telemetryServer->server_id);
            $linkedServerPayload = null;

            if ($linkedServer instanceof Server) {
                $linkedServerPayload = [
                    'id' => $linkedServer->id,
                    'uuid' => $linkedServer->uuid,
                    'name' => $this->resolveServerName($this->decodeConfig($linkedServer->config)),
                    'status' => $linkedServer->status,
                    'plan' => $linkedServer->plan,
                    'owner' => $this->transformUser($linkedServer->user),
                ];
            }

            return [
                'server_id' => $telemetryServer->server_id,
                'players_online' => $telemetryServer->players_online,
                'cpu_pct' => (float) $telemetryServer->cpu_pct,
                'io_write_bytes_per_s' => (float) $telemetryServer->io_write_bytes_per_s,
                'last_reported_at' => $telemetryServer->updated_at?->toIso8601String(),
                'server' => $linkedServerPayload,
            ];
        })->all();
    }

    /**
     * @param  Collection<int, TelemetryServer>  $telemetryServers
     * @return Collection<string, Server>
     */
    private function resolveServersByTelemetryIdentifier(Collection $telemetryServers): Collection
    {
        /** @var Collection<int, string> $telemetryIdentifiers */
        $telemetryIdentifiers = $telemetryServers
            ->pluck('server_id')
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->values();

        if ($telemetryIdentifiers->isEmpty()) {
            return collect();
        }

        /** @var Collection<int, int> $numericIds */
        $numericIds = $telemetryIdentifiers
            ->filter(static fn (string $value): bool => ctype_digit($value))
            ->map(static fn (string $value): int => (int) $value)
            ->values();

        $servers = Server::query()
            ->with('user')
            ->where(function ($query) use ($telemetryIdentifiers, $numericIds): void {
                $query->whereIn('uuid', $telemetryIdentifiers->all());

                if ($numericIds->isNotEmpty()) {
                    $query->orWhereIn('id', $numericIds->all());
                }
            })
            ->get();

        $serversByIdentifier = collect();

        foreach ($servers as $server) {
            $serversByIdentifier->put((string) $server->id, $server);

            if (is_string($server->uuid) && trim($server->uuid) !== '') {
                $serversByIdentifier->put($server->uuid, $server);
            }
        }

        return $serversByIdentifier;
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
     * @param  array<string, mixed>  $config
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
}
