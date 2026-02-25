<?php

declare(strict_types=1);

namespace App\Services\EventBus;

final class ServerLifecycleEventProcessorMap
{
    public const SERVER_ORDERED_PROCESSOR_KEY = 'server_ordered_lifecycle';
    public const SERVER_PROVISIONED_PROCESSOR_KEY = 'server_provisioned_lifecycle';

    /**
     * @var array<string, list<string>>
     */
    private const EVENT_TYPE_TO_PROCESSOR_KEYS = [
        'server.ordered.v1' => [
            self::SERVER_ORDERED_PROCESSOR_KEY,
        ],
        'server.provisioned' => [
            self::SERVER_PROVISIONED_PROCESSOR_KEY,
        ],
        'server.provisioned.v1' => [
            self::SERVER_PROVISIONED_PROCESSOR_KEY,
        ],
    ];

    /**
     * @return list<string>
     */
    public function processorKeysForEventType(string $eventType): array
    {
        return self::EVENT_TYPE_TO_PROCESSOR_KEYS[$eventType] ?? [];
    }
}
