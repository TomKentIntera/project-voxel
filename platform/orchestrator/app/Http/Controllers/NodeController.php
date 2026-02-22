<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Interadigital\CoreModels\Models\Node;
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

        return response()->json([
            'data' => $this->transformNode($node),
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
}
