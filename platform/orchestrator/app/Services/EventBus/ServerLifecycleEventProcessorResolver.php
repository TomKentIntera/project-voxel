<?php

declare(strict_types=1);

namespace App\Services\EventBus;

use RuntimeException;

final class ServerLifecycleEventProcessorResolver
{
    public function __construct(
        private readonly ServerOrderedLifecycleEventConsumer $serverOrderedLifecycleEventConsumer,
        private readonly ServerProvisionedLifecycleEventConsumer $serverProvisionedLifecycleEventConsumer,
    ) {}

    public function resolve(string $processorKey): ServerLifecycleEventConsumer
    {
        return match ($processorKey) {
            ServerLifecycleEventProcessorMap::SERVER_ORDERED_PROCESSOR_KEY => $this->serverOrderedLifecycleEventConsumer,
            ServerLifecycleEventProcessorMap::SERVER_PROVISIONED_PROCESSOR_KEY => $this->serverProvisionedLifecycleEventConsumer,
            default => throw new RuntimeException(sprintf(
                'Unsupported server lifecycle processor key "%s".',
                $processorKey,
            )),
        };
    }
}
