<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Interadigital\CoreModels\Models\RegionalProxy;
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
     * @return array<string, mixed>
     */
    private function transformProxy(RegionalProxy $regionalProxy): array
    {
        return [
            'id' => $regionalProxy->id,
            'name' => $regionalProxy->name,
            'region' => $regionalProxy->region,
            'last_used_at' => $regionalProxy->last_used_at?->toIso8601String(),
            'created_at' => $regionalProxy->created_at?->toIso8601String(),
            'updated_at' => $regionalProxy->updated_at?->toIso8601String(),
        ];
    }
}

