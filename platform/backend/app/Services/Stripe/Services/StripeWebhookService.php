<?php

declare(strict_types=1);

namespace App\Services\Stripe\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Interadigital\CoreEvents\EventBus\EventBusClient;
use Interadigital\CoreEvents\Events\ServerOrdered;
use Interadigital\CoreModels\Enums\ServerEventType;
use Interadigital\CoreModels\Enums\ServerStatus;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\ServerEvent;

class StripeWebhookService
{
    public function __construct(
        private readonly EventBusClient $eventBusClient
    ) {
    }

    /**
     * @param  array<string, mixed>  $eventPayload
     */
    public function handleEvent(array $eventPayload): void
    {
        $eventType = $eventPayload['type'] ?? null;

        if (! is_string($eventType) || $eventType === '') {
            return;
        }

        switch ($eventType) {
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($eventPayload);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($eventPayload);
                break;

            default:
                Log::info('Unhandled Stripe webhook event.', ['type' => $eventType]);
                break;
        }
    }

    /**
     * @param  array<string, mixed>  $eventPayload
     */
    private function handleInvoicePaymentSucceeded(array $eventPayload): void
    {
        $subscriptionId = $eventPayload['data']['object']['subscription'] ?? null;

        if (! is_string($subscriptionId) || $subscriptionId === '') {
            return;
        }

        $server = Server::where('stripe_tx_id', $subscriptionId)->first();

        if ($server === null) {
            return;
        }

        $shouldPublishOrder = ! (bool) $server->initialised
            && ! $server->events()->where('type', ServerEventType::SERVER_ORDERED->value)->exists();

        $server->fill([
            'stripe_tx_return' => true,
            'suspended' => false,
            'status' => $shouldPublishOrder
                ? ServerStatus::PROVISIONING->value
                : ServerStatus::ACTIVE->value,
        ]);
        $server->save();

        if (! $shouldPublishOrder) {
            return;
        }

        $event = new ServerOrdered(
            eventId: $this->eventId($eventPayload),
            occurredAt: $this->occurredAt($eventPayload),
            serverId: (int) $server->id,
            serverUuid: (string) $server->uuid,
            userId: (int) $server->user_id,
            plan: (string) $server->plan,
            config: $this->decodeServerConfig($server),
            stripeSubscriptionId: $subscriptionId,
            correlationId: $subscriptionId,
        );

        $this->eventBusClient->publish($event);

        ServerEvent::query()->create([
            'server_id' => $server->id,
            'actor_id' => null,
            'type' => ServerEventType::SERVER_ORDERED->value,
            'meta' => [
                'event_id' => $event->eventId,
                'stripe_subscription_id' => $subscriptionId,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $eventPayload
     */
    private function handleSubscriptionDeleted(array $eventPayload): void
    {
        $subscriptionId = $eventPayload['data']['object']['id'] ?? null;

        if (! is_string($subscriptionId) || $subscriptionId === '') {
            return;
        }

        $server = Server::where('stripe_tx_id', $subscriptionId)->first();

        if ($server === null) {
            return;
        }

        $server->fill([
            'suspended' => true,
            'status' => ServerStatus::SUSPENDED->value,
        ]);
        $server->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeServerConfig(Server $server): array
    {
        $rawConfig = $server->config;

        if (! is_string($rawConfig) || trim($rawConfig) === '') {
            return [];
        }

        $decoded = json_decode($rawConfig, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $eventPayload
     */
    private function eventId(array $eventPayload): string
    {
        $id = $eventPayload['id'] ?? null;

        if (is_string($id) && trim($id) !== '') {
            return trim($id);
        }

        return (string) Str::uuid();
    }

    /**
     * @param array<string, mixed> $eventPayload
     */
    private function occurredAt(array $eventPayload): string
    {
        $created = $eventPayload['created'] ?? null;

        if (is_int($created)) {
            return Carbon::createFromTimestampUTC($created)->toIso8601String();
        }

        if (is_string($created) && ctype_digit($created)) {
            return Carbon::createFromTimestampUTC((int) $created)->toIso8601String();
        }

        return now()->toIso8601String();
    }
}
