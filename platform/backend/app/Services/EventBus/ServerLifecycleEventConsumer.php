<?php

declare(strict_types=1);

namespace App\Services\EventBus;

interface ServerLifecycleEventConsumer
{
    /**
     * @param array<string, mixed> $eventPayload
     */
    public function consume(array $eventPayload): void;
}
