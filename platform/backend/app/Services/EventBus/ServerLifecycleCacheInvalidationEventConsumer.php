<?php

declare(strict_types=1);

namespace App\Services\EventBus;

use App\Services\LocationsCacheReader;

final class ServerLifecycleCacheInvalidationEventConsumer implements ServerLifecycleEventConsumer
{
    public function __construct(
        private readonly LocationsCacheReader $locationsCacheReader
    ) {}

    /**
     * @param array<string, mixed> $eventPayload
     */
    public function consume(array $eventPayload): void
    {
        $this->locationsCacheReader->forgetCachedPayload();
    }
}
