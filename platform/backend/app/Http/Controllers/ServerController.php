<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PterodactylPanelLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    /**
     * Return the authenticated user's servers with resolved plan data.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $servers = $user->servers()->orderByDesc('created_at')->get();

        $planMap = collect(config('plans.planList', []))->keyBy('name');

        $items = $servers->map(function ($server) use ($planMap) {
            $config = $server->config ? json_decode($server->config, true) : null;
            $plan = $planMap->get($server->plan);

            return [
                'id' => $server->id,
                'uuid' => $server->uuid,
                'name' => $config['name'] ?? 'Unnamed Server',
                'created_at' => $server->created_at?->toIso8601String(),
                'suspended' => (bool) $server->suspended,
                'status' => $server->status,
                'stripe_tx_return' => (bool) $server->stripe_tx_return,
                'plan' => $plan ? [
                    'name' => $plan['name'],
                    'title' => $plan['title'],
                    'ram' => $plan['ram'],
                ] : null,
            ];
        });

        return response()->json([
            'servers' => $items,
        ]);
    }

    /**
     * Resolve the panel URL for a server owned by the current user.
     */
    public function panelUrl(
        Request $request,
        string $uuid,
        PterodactylPanelLinkService $panelLinkService
    ): JsonResponse {
        $user = $request->user();
        $server = $user->servers()->where('uuid', $uuid)->firstOrFail();

        if (! (bool) $server->stripe_tx_return || ! (bool) $server->initialised) {
            return response()->json([
                'message' => 'Server is still provisioning.',
            ], 409);
        }

        $panelUrl = $panelLinkService->resolvePanelUrl($server);
        if ($panelUrl === null) {
            return response()->json([
                'message' => 'Pterodactyl panel URL is not configured.',
            ], 503);
        }

        return response()->json([
            'panel_url' => $panelUrl,
        ]);
    }
}

