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

    private function mappingResponseForProxy(RegionalProxy $regionalProxy): JsonResponse
    {
        $now = now();

        $regionalProxy->forceFill([
            'last_active_at' => $now,
            'last_used_at' => $now,
        ])->save();

        $regionalProxy->refresh();

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

