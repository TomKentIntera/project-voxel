<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Interadigital\CoreModels\Enums\UserRole;
use Interadigital\CoreModels\Models\User;

class UserController extends Controller
{
    /**
     * Paginated, searchable user listing.
     *
     * Query parameters:
     *  - search : free-text search across name, email, username, first_name, last_name
     *  - role   : filter by role (admin, customer)
     *  - page   : page number (default 1)
     *  - per_page : items per page (default 15, max 100)
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->withCount('servers');

        // Free-text search
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($role = $request->string('role')->trim()->toString()) {
            $roleEnum = UserRole::tryFrom($role);
            if ($roleEnum !== null) {
                $query->where('role', $roleEnum->value);
            }
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        $items = collect($paginator->items())->map(fn (User $user): array => [
            'id' => $user->id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role instanceof UserRole ? $user->role->value : $user->role,
            'servers_count' => $user->servers_count ?? 0,
            'created_at' => $user->created_at?->toIso8601String(),
        ]);

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
     * Return a single user's profile with their servers.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::withCount('servers')->with('servers')->find($id);

        if ($user === null) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $servers = $user->servers->map(fn ($server): array => [
            'id' => $server->id,
            'uuid' => $server->uuid,
            'status' => $server->status,
            'plan' => $server->plan,
            'created_at' => $server->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role instanceof UserRole ? $user->role->value : $user->role,
                'servers_count' => $user->servers_count ?? 0,
                'servers' => $servers,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ]);
    }
}

