<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PterodactylPanelLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ServerController extends Controller
{
    /**
     * Return the authenticated user's servers with resolved plan data.
     *
     * @OA\Get(
     *     path="/api/servers",
     *     operationId="getUserServers",
     *     tags={"Servers"},
     *     summary="List servers for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Server list",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"servers"},
     *             @OA\Property(
     *                 property="servers",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ServerSummary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiMessage")
     *     )
     * )
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
     *
     * @OA\Get(
     *     path="/api/servers/{uuid}/panel-url",
     *     operationId="getServerPanelUrl",
     *     tags={"Servers"},
     *     summary="Get panel URL for a server",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Server UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Panel URL resolved",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"panel_url"},
     *             @OA\Property(
     *                 property="panel_url",
     *                 type="string",
     *                 format="uri",
     *                 example="https://panel.example.com/server/fdbf7b6f-1a44-482f-9883-3968d3fe1270"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiMessage")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Server not found for this user",
     *         @OA\JsonContent(ref="#/components/schemas/ApiMessage")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Server is still provisioning",
     *         @OA\JsonContent(ref="#/components/schemas/ApiMessage")
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="Panel URL is not configured",
     *         @OA\JsonContent(ref="#/components/schemas/ApiMessage")
     *     )
     * )
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

