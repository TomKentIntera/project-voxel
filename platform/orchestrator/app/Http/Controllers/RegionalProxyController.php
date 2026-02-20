<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Interadigital\CoreModels\Models\RegionalProxy;
use Interadigital\CoreModels\Models\Server;
use Symfony\Component\HttpFoundation\Response;

class RegionalProxyController extends Controller
{
    private const BINDING_KIND_GAME = 'game';

    private const BINDING_KIND_SFTP = 'sftp';

    /**
     * @var list<string>
     */
    private const SUPPORTED_BINDING_KINDS = [
        self::BINDING_KIND_GAME,
        self::BINDING_KIND_SFTP,
    ];

    /**
     * Paginated, searchable regional proxy listing.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RegionalProxy::query();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        $items = collect($paginator->items())->map(
            fn (RegionalProxy $regionalProxy): array => $this->transformProxy($regionalProxy)
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

    /**
     * Return a single regional proxy.
     */
    public function show(int $id): JsonResponse
    {
        $regionalProxy = RegionalProxy::find($id);

        if ($regionalProxy === null) {
            return response()->json([
                'message' => 'Regional proxy not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $this->transformProxy($regionalProxy),
        ]);
    }

    /**
     * Create a regional proxy and return its one-time raw token.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:regional_proxies,name'],
            'region' => ['required', 'string', 'max:255'],
        ]);

        $rawToken = RegionalProxy::generateToken();

        $regionalProxy = RegionalProxy::create([
            'name' => $validated['name'],
            'region' => $validated['region'],
            'token_hash' => RegionalProxy::hashToken($rawToken),
        ]);

        return response()->json([
            'data' => [
                ...$this->transformProxy($regionalProxy),
                'proxy_token' => $rawToken,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Return server mappings for the authenticated regional proxy token.
     */
    public function mappings(Request $request): JsonResponse
    {
        $regionalProxy = $this->resolveRegionalProxyFromRequest($request);

        if ($regionalProxy === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->mappingResponseForProxy($regionalProxy);
    }

    /**
     * Return server mappings for a specific regional proxy.
     */
    public function mappingsById(Request $request, int $id): JsonResponse
    {
        $regionalProxy = $this->resolveRegionalProxyFromRequest($request);

        if ($regionalProxy === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($regionalProxy->id !== $id) {
            return response()->json([
                'message' => 'Access denied for the requested regional proxy.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->mappingResponseForProxy($regionalProxy);
    }

    /**
     * Return TCP proxy bindings for the authenticated regional proxy token.
     */
    public function bindings(Request $request): JsonResponse
    {
        $regionalProxy = $this->resolveRegionalProxyFromRequest($request);

        if ($regionalProxy === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->bindingResponseForProxy($regionalProxy);
    }

    private function mappingResponseForProxy(RegionalProxy $regionalProxy): JsonResponse
    {
        $regionalProxy = $this->touchRegionalProxyActivity($regionalProxy);

        $mappings = $this->buildMappingsForRegion($regionalProxy->region);

        return response()->json([
            'data' => [
                'regional_proxy' => $this->transformProxy($regionalProxy),
                'mappings' => $mappings,
            ],
            'meta' => [
                'count' => count($mappings),
            ],
        ]);
    }

    private function bindingResponseForProxy(RegionalProxy $regionalProxy): JsonResponse
    {
        $regionalProxy = $this->touchRegionalProxyActivity($regionalProxy);

        return response()->json($this->buildBindingsForRegion($regionalProxy->region));
    }

    private function touchRegionalProxyActivity(RegionalProxy $regionalProxy): RegionalProxy
    {
        $now = now();

        $regionalProxy->forceFill([
            'last_active_at' => $now,
            'last_used_at' => $now,
        ])->save();

        return $regionalProxy->refresh();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildMappingsForRegion(string $region): array
    {
        return Server::query()
            ->select(['id', 'uuid', 'ptero_id', 'status', 'config'])
            ->orderBy('id')
            ->get()
            ->map(function (Server $server) use ($region): ?array {
                $config = $this->decodeServerConfig($server->config);
                $serverRegion = $this->resolveServerRegionFromConfig($config);

                if ($serverRegion !== $region) {
                    return null;
                }

                return [
                    'server_id' => $server->id,
                    'server_uuid' => $server->uuid,
                    'ptero_id' => $server->ptero_id,
                    'status' => $server->status,
                    'region' => $serverRegion,
                ];
            })
            ->filter(static fn (?array $mapping): bool => $mapping !== null)
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildBindingsForRegion(string $region): array
    {
        $bindings = [];

        $servers = Server::query()
            ->select(['id', 'uuid', 'config', 'updated_at'])
            ->orderBy('id')
            ->get();

        foreach ($servers as $server) {
            $config = $this->decodeServerConfig($server->config);
            $serverRegion = $this->resolveServerRegionFromConfig($config);

            if ($serverRegion !== $region) {
                continue;
            }

            foreach ($this->extractBindingsFromConfig($config) as $rawBinding) {
                $normalizedBinding = $this->normalizeBinding($rawBinding, $server);

                if ($normalizedBinding !== null) {
                    $bindings[] = $normalizedBinding;
                }
            }
        }

        usort($bindings, static function (array $left, array $right): int {
            $kindComparison = strcmp((string) $left['kind'], (string) $right['kind']);

            if ($kindComparison !== 0) {
                return $kindComparison;
            }

            return ((int) $left['listen_port']) <=> ((int) $right['listen_port']);
        });

        return $bindings;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array<string, mixed>>
     */
    private function extractBindingsFromConfig(array $config): array
    {
        $bindings = [];

        $this->appendBindingList($bindings, $config['proxy_bindings'] ?? null);
        $this->appendBindingList($bindings, $config['bindings'] ?? null);

        $proxyConfig = $config['proxy'] ?? null;

        if (is_array($proxyConfig)) {
            $this->appendBindingList($bindings, $proxyConfig['bindings'] ?? null);

            $gameProxyConfig = $proxyConfig['game'] ?? null;
            if (is_array($gameProxyConfig)) {
                $bindings[] = [
                    'kind' => self::BINDING_KIND_GAME,
                    ...$gameProxyConfig,
                ];
            }

            $sftpProxyConfig = $proxyConfig['sftp'] ?? null;
            if (is_array($sftpProxyConfig)) {
                $bindings[] = [
                    'kind' => self::BINDING_KIND_SFTP,
                    ...$sftpProxyConfig,
                ];
            }
        }

        $flatGameBinding = $this->buildFlatBindingFromConfig($config, self::BINDING_KIND_GAME);
        if ($flatGameBinding !== null) {
            $bindings[] = $flatGameBinding;
        }

        $flatSftpBinding = $this->buildFlatBindingFromConfig($config, self::BINDING_KIND_SFTP);
        if ($flatSftpBinding !== null) {
            $bindings[] = $flatSftpBinding;
        }

        return $bindings;
    }

    /**
     * @param array<int, array<string, mixed>> $bindings
     */
    private function appendBindingList(array &$bindings, mixed $candidateBindings): void
    {
        if (! is_array($candidateBindings)) {
            return;
        }

        foreach ($candidateBindings as $candidateBinding) {
            if (is_array($candidateBinding)) {
                $bindings[] = $candidateBinding;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    private function buildFlatBindingFromConfig(array $config, string $kind): ?array
    {
        if ($kind === self::BINDING_KIND_GAME) {
            $listenPortKeys = ['game_listen_port', 'proxy_game_listen_port'];
            $targetHostKeys = ['game_target_host', 'game_host', 'target_host', 'host', 'target_ip'];
            $targetPortKeys = ['game_target_port', 'game_port', 'minecraft_port'];
            $enabledKeys = ['game_enabled', 'proxy_game_enabled'];
        } else {
            $listenPortKeys = ['sftp_listen_port', 'proxy_sftp_listen_port'];
            $targetHostKeys = ['sftp_target_host', 'sftp_host', 'target_host', 'host', 'target_ip'];
            $targetPortKeys = ['sftp_target_port', 'sftp_port'];
            $enabledKeys = ['sftp_enabled', 'proxy_sftp_enabled'];
        }

        $listenPort = $this->parsePort($this->firstConfigValue($config, $listenPortKeys));
        $targetHostValue = $this->firstConfigValue($config, $targetHostKeys);
        $targetPort = $this->parsePort($this->firstConfigValue($config, $targetPortKeys));
        $enabledValue = $this->firstConfigValue($config, $enabledKeys);

        $targetHost = is_string($targetHostValue) ? trim($targetHostValue) : '';

        if ($listenPort === null || $targetPort === null || $targetHost === '') {
            return null;
        }

        return [
            'kind' => $kind,
            'listen_port' => $listenPort,
            'target_host' => $targetHost,
            'target_port' => $targetPort,
            'enabled' => $enabledValue ?? true,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  list<string>  $keys
     */
    private function firstConfigValue(array $config, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $config)) {
                return $config[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $rawBinding
     * @return array<string, mixed>|null
     */
    private function normalizeBinding(array $rawBinding, Server $server): ?array
    {
        $kind = strtolower(trim((string) ($rawBinding['kind'] ?? '')));

        if (! in_array($kind, self::SUPPORTED_BINDING_KINDS, true)) {
            return null;
        }

        $listenPort = $this->parsePort($rawBinding['listen_port'] ?? null);
        $targetPort = $this->parsePort($rawBinding['target_port'] ?? null);
        $targetHost = trim((string) ($rawBinding['target_host'] ?? ''));

        if ($listenPort === null || $targetPort === null || $targetHost === '') {
            return null;
        }

        $updatedAt = $rawBinding['updated_at'] ?? null;
        if (! is_string($updatedAt) || trim($updatedAt) === '') {
            $updatedAt = $server->updated_at?->toIso8601String() ?? now()->toIso8601String();
        } else {
            $updatedAt = trim($updatedAt);
        }

        return [
            'kind' => $kind,
            'listen_port' => $listenPort,
            'target_host' => $targetHost,
            'target_port' => $targetPort,
            'enabled' => array_key_exists('enabled', $rawBinding) ? (bool) $rawBinding['enabled'] : true,
            'updated_at' => $updatedAt,
        ];
    }

    private function parsePort(mixed $value): ?int
    {
        if (is_int($value)) {
            $port = $value;
        } elseif (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '' || ! ctype_digit($trimmed)) {
                return null;
            }

            $port = (int) $trimmed;
        } else {
            return null;
        }

        if ($port < 1 || $port > 65535) {
            return null;
        }

        return $port;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveServerRegionFromConfig(array $config): ?string
    {
        $explicitRegion = $config['region'] ?? null;
        if (is_string($explicitRegion) && trim($explicitRegion) !== '') {
            return trim($explicitRegion);
        }

        $explicitPteroLocation = $config['ptero_location'] ?? null;
        if (is_string($explicitPteroLocation) && trim($explicitPteroLocation) !== '') {
            return trim($explicitPteroLocation);
        }

        $locationCode = $config['location'] ?? null;
        if (! is_string($locationCode) || trim($locationCode) === '') {
            return null;
        }

        $pteroLocation = config('plans.locations.'.trim($locationCode).'.ptero_location');

        return is_string($pteroLocation) && trim($pteroLocation) !== ''
            ? trim($pteroLocation)
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeServerConfig(?string $config): array
    {
        if (! is_string($config) || $config === '') {
            return [];
        }

        $decoded = json_decode($config, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolveRegionalProxyFromRequest(Request $request): ?RegionalProxy
    {
        $regionalProxy = $request->attributes->get('regionalProxy');

        return $regionalProxy instanceof RegionalProxy ? $regionalProxy : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformProxy(RegionalProxy $regionalProxy): array
    {
        return [
            'id' => $regionalProxy->id,
            'name' => $regionalProxy->name,
            'region' => $regionalProxy->region,
            'last_active_at' => $regionalProxy->last_active_at?->toIso8601String(),
            'last_used_at' => $regionalProxy->last_used_at?->toIso8601String(),
            'created_at' => $regionalProxy->created_at?->toIso8601String(),
            'updated_at' => $regionalProxy->updated_at?->toIso8601String(),
        ];
    }
}

