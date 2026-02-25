<?php

declare(strict_types=1);

namespace App\Services\EventBus;

use RuntimeException;

final class ServerLifecycleEventProcessorResolver
{
    public function __construct(
        private readonly ServerLifecycleCacheInvalidationEventConsumer $serverLifecycleCacheInvalidationEventConsumer,
    ) {}

    public function resolve(string $processorKey): ServerLifecycleEventConsumer
    {
        return match ($processorKey) {
            ServerLifecycleEventProcessorMap::CACHE_INVALIDATION_PROCESSOR_KEY => $this->serverLifecycleCacheInvalidationEventConsumer,
            default => throw new RuntimeException(sprintf(
                'Unsupported server lifecycle processor key "%s".',
                $processorKey,
            )),
        };
    }
}
