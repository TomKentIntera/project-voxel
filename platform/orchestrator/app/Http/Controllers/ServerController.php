<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\ServerEvent;
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
            ],
        ]);
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
            'server.provisioned' => 'Server provisioned',
            'server.cancelled' => 'Server cancelled',
            'server.suspended' => 'Server suspended',
            default => ucwords(str_replace(['.', '_', '-'], ' ', $type)),
        };
    }
}

