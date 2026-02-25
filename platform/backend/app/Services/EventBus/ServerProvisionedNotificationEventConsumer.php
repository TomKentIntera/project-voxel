<?php

declare(strict_types=1);

namespace App\Services\EventBus;

final class ServerProvisionedNotificationEventConsumer implements ServerLifecycleEventConsumer
{
    public function __construct(
        private readonly ServerProvisionedNotificationDispatcher $serverProvisionedNotificationDispatcher
    ) {}

    /**
     * @param array<string, mixed> $eventPayload
     */
    public function consume(array $eventPayload): void
    {
        $this->serverProvisionedNotificationDispatcher->dispatch($eventPayload);
    }
}
