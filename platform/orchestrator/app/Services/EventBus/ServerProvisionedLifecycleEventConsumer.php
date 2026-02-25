<?php

declare(strict_types=1);

namespace App\Services\EventBus;

use Illuminate\Support\Facades\Log;
use Interadigital\CoreModels\Enums\ServerEventType;
use Interadigital\CoreModels\Enums\ServerStatus;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\ServerEvent;

final class ServerProvisionedLifecycleEventConsumer implements ServerLifecycleEventConsumer
{
    public function __construct(
        private readonly ServerProvisionedNotificationDispatcher $serverProvisionedNotificationDispatcher
    ) {}

    /**
     * @param array<string, mixed> $eventPayload
     */
    public function consume(array $eventPayload): void
    {
        $serverId = $this->extractServerId($eventPayload);
        if ($serverId === null) {
            Log::warning('Dropping server provisioned event missing server_id.', [
                'event_id' => $eventPayload['event_id'] ?? null,
            ]);

            return;
        }

        $server = Server::query()->with('user')->find($serverId);
        if (! ($server instanceof Server)) {
            Log::warning('Received server provisioned event for unknown server.', [
                'server_id' => $serverId,
                'event_id' => $eventPayload['event_id'] ?? null,
            ]);

            return;
        }

        $eventId = $this->extractEventId($eventPayload);
        if ($eventId !== null && $this->isDuplicateProvisionedEvent($server, $eventId)) {
            return;
        }

        if ($server->status !== ServerStatus::PROVISIONED->value || ! (bool) $server->initialised) {
            $server->status = ServerStatus::PROVISIONED->value;
            $server->initialised = true;
            $server->save();
        }

        ServerEvent::query()->create([
            'server_id' => $server->id,
            'actor_id' => null,
            'type' => ServerEventType::SERVER_PROVISIONED->value,
            'meta' => [
                'event_id' => $eventId,
                'event_type' => $this->extractEventType($eventPayload),
                'occurred_at' => $this->extractOptionalString($eventPayload, 'occurred_at'),
                'correlation_id' => $this->extractOptionalString($eventPayload, 'correlation_id'),
            ],
        ]);

        $this->serverProvisionedNotificationDispatcher->dispatch($server);
    }

    /**
     * @param array<string, mixed> $eventPayload
     */
    private function extractServerId(array $eventPayload): ?int
    {
        $serverId = $eventPayload['server_id'] ?? null;

        if (is_int($serverId) && $serverId > 0) {
            return $serverId;
        }

        if (is_string($serverId) && ctype_digit($serverId)) {
            $normalized = (int) $serverId;

            return $normalized > 0 ? $normalized : null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $eventPayload
     */
    private function extractEventId(array $eventPayload): ?string
    {
        $eventId = $eventPayload['event_id'] ?? null;

        if (! is_string($eventId)) {
            return null;
        }

        $normalized = trim($eventId);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param array<string, mixed> $eventPayload
     */
    private function extractEventType(array $eventPayload): ?string
    {
        return $this->extractOptionalString($eventPayload, 'event_type');
    }

    /**
     * @param array<string, mixed> $eventPayload
     */
    private function extractOptionalString(array $eventPayload, string $key): ?string
    {
        $value = $eventPayload[$key] ?? null;
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    private function isDuplicateProvisionedEvent(Server $server, string $eventId): bool
    {
        return $server->events()
            ->where('type', ServerEventType::SERVER_PROVISIONED->value)
            ->get()
            ->contains(static fn (ServerEvent $entry): bool => ($entry->meta['event_id'] ?? null) === $eventId);
    }
}
