<?php

declare(strict_types=1);

namespace App\Services\EventBus;

use Illuminate\Support\Facades\Log;
use Interadigital\CoreEvents\Events\ServerOrdered;
use Interadigital\CoreModels\Enums\ServerEventType;
use Interadigital\CoreModels\Enums\ServerStatus;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\ServerEvent;
use Throwable;

final class ServerOrderedLifecycleEventConsumer implements ServerLifecycleEventConsumer
{
    /**
     * @param array<string, mixed> $eventPayload
     */
    public function consume(array $eventPayload): void
    {
        try {
            $event = ServerOrdered::fromArray($eventPayload);
        } catch (Throwable) {
            Log::warning('Dropping malformed server ordered event payload.', [
                'event_type' => $eventPayload['event_type'] ?? null,
                'event_id' => $eventPayload['event_id'] ?? null,
            ]);

            return;
        }

        $server = Server::query()
            ->where('id', $event->serverId)
            ->where('uuid', $event->serverUuid)
            ->first();

        if ($server === null) {
            Log::warning('Received server ordered event for unknown server.', [
                'server_id' => $event->serverId,
                'server_uuid' => $event->serverUuid,
                'event_id' => $event->eventId,
            ]);

            return;
        }

        $alreadyProcessed = $server->events()
            ->where('type', ServerEventType::SERVER_PROVISIONING_STARTED->value)
            ->get()
            ->contains(fn (ServerEvent $entry): bool => ($entry->meta['event_id'] ?? null) === $event->eventId);

        if ($alreadyProcessed) {
            return;
        }

        if ($server->status !== ServerStatus::PROVISIONING->value) {
            $server->status = ServerStatus::PROVISIONING->value;
            $server->save();
        }

        ServerEvent::query()->create([
            'server_id' => $server->id,
            'actor_id' => null,
            'type' => ServerEventType::SERVER_PROVISIONING_STARTED->value,
            'meta' => [
                'event_id' => $event->eventId,
                'event_type' => ServerOrdered::eventType(),
                'correlation_id' => $event->correlationId,
                'stripe_subscription_id' => $event->stripeSubscriptionId,
            ],
        ]);
    }
}
