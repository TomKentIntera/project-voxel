<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PterodactylPanelLinkService;
use App\Services\Stripe\Services\StripeCheckoutSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Interadigital\CoreModels\Enums\ServerStatus;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\User;
use InvalidArgumentException;
use Throwable;
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

    /**
     * Return purchase/provisioning status for a server owned by the current user.
     */
    public function provisioningStatus(
        Request $request,
        string $uuid,
        PterodactylPanelLinkService $panelLinkService
    ): JsonResponse {
        $user = $request->user();
        $server = $user->servers()->where('uuid', $uuid)->firstOrFail();

        $paymentConfirmed = (bool) $server->stripe_tx_return;
        $initialised = (bool) $server->initialised;
        $isProvisioned = $paymentConfirmed && $initialised;
        $stage = ! $paymentConfirmed
            ? 'pending'
            : ($initialised ? 'provisioned' : 'provisioning');

        $panelUrl = null;
        if ($isProvisioned) {
            $panelUrl = $panelLinkService->resolvePanelUrl($server);
        }

        return response()->json([
            'server_uuid' => (string) $server->uuid,
            'status' => (string) $server->status,
            'payment_confirmed' => $paymentConfirmed,
            'initialised' => $initialised,
            'provisioned' => $isProvisioned,
            'stage' => $stage,
            'panel_url' => $panelUrl,
        ]);
    }

    /**
     * Confirm the checkout return token and attach Stripe subscription to server.
     */
    public function confirmPurchaseReturn(
        Request $request,
        string $uuid,
        StripeCheckoutSessionService $stripeCheckoutSessionService
    ): JsonResponse {
        $user = $request->user();
        $server = $user->servers()->where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:255'],
        ]);

        try {
            $checkoutSession = $stripeCheckoutSessionService->retrieveCheckoutSession(
                (string) $validated['session_id']
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Unable to verify checkout session.',
            ], 502);
        }

        $metadataServerUuid = is_object($checkoutSession->metadata ?? null)
            ? ($checkoutSession->metadata->server_uuid ?? null)
            : null;

        if (is_string($metadataServerUuid) && $metadataServerUuid !== '' && $metadataServerUuid !== $server->uuid) {
            return response()->json([
                'message' => 'Checkout session does not match this server.',
            ], 422);
        }

        $subscriptionId = $checkoutSession->subscription ?? null;
        if (is_object($subscriptionId) && isset($subscriptionId->id) && is_string($subscriptionId->id)) {
            $subscriptionId = $subscriptionId->id;
        }

        if (! is_string($subscriptionId) || $subscriptionId === '') {
            return response()->json([
                'message' => 'Checkout session does not contain a subscription yet.',
            ], 422);
        }

        $server->stripe_tx_id = $subscriptionId;
        $server->save();

        return response()->json([
            'server_uuid' => (string) $server->uuid,
            'stripe_subscription_id' => $subscriptionId,
            'checkout_status' => (string) ($checkoutSession->status ?? ''),
            'payment_status' => (string) ($checkoutSession->payment_status ?? ''),
        ]);
    }

    /**
     * Create a server record in pending-payment state, then open Stripe checkout.
     */
    public function purchase(
        Request $request,
        StripeCheckoutSessionService $stripeCheckoutSessionService
    ): JsonResponse {
        $user = $request->user();
        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'plan' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:64'],
            'location' => ['required', 'string'],
            'minecraft_version' => ['required', 'string', 'max:64'],
            'type' => ['required', 'string', 'max:64'],
            'type_version' => ['nullable', 'string', 'max:128'],
        ]);

        $planName = strtolower(trim((string) $validated['plan']));
        $plansByName = collect(config('plans.planList', []))
            ->filter(fn (mixed $plan): bool => is_array($plan) && isset($plan['name']))
            ->keyBy(fn (array $plan): string => strtolower((string) $plan['name']));

        /** @var array<string, mixed>|null $plan */
        $plan = $plansByName->get($planName);
        if ($plan === null) {
            return response()->json([
                'message' => 'Selected plan is invalid.',
            ], 422);
        }

        $location = (string) $validated['location'];
        $planLocations = is_array($plan['locations'] ?? null) ? $plan['locations'] : [];
        if (! in_array($location, $planLocations, true)) {
            return response()->json([
                'message' => 'Selected location is invalid for this plan.',
            ], 422);
        }

        $serverConfig = [
            'name' => trim((string) ($validated['name'] ?? '')) !== ''
                ? trim((string) $validated['name'])
                : 'My Server',
            'location' => $location,
            'minecraft_version' => (string) $validated['minecraft_version'],
            'type' => (string) $validated['type'],
            'type_version' => $validated['type_version'] ?? null,
        ];

        $server = Server::query()->create([
            'stripe_tx_id' => null,
            'config' => json_encode($serverConfig),
            'plan' => (string) $plan['name'],
            'uuid' => (string) Str::uuid(),
            'initialised' => false,
            'stripe_tx_return' => false,
            'user_id' => (int) $user->id,
            'suspended' => false,
            'status' => ServerStatus::NEW->value,
        ]);

        $completeBaseUrl = rtrim((string) config('stripe.checkout_complete_base_url'), '/');
        $successUrl = $completeBaseUrl.'/'.(string) $server->uuid.'?session_id={CHECKOUT_SESSION_ID}';

        try {
            $checkoutSession = $stripeCheckoutSessionService->createSubscriptionCheckoutSession(
                $user,
                (string) $plan['name'],
                $successUrl,
                null,
                null,
                [
                    'server_uuid' => (string) $server->uuid,
                ]
            );
        } catch (InvalidArgumentException $exception) {
            $server->delete();

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);
            $server->delete();

            return response()->json([
                'message' => 'Unable to start checkout session.',
            ], 502);
        }

        $checkoutUrl = is_string($checkoutSession->url ?? null) ? $checkoutSession->url : '';
        if ($checkoutUrl === '') {
            $server->delete();

            return response()->json([
                'message' => 'Checkout URL is missing from Stripe response.',
            ], 502);
        }

        return response()->json([
            'server_uuid' => (string) $server->uuid,
            'checkout_url' => $checkoutUrl,
        ], 201);
    }
}

