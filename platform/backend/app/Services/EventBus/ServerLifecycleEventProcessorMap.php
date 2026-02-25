<?php

declare(strict_types=1);

namespace App\Services\EventBus;

final class ServerLifecycleEventProcessorMap
{
    public const CACHE_INVALIDATION_PROCESSOR_KEY = 'cache_invalidation';

    /**
     * @var array<string, list<string>>
     */
    private const EVENT_TYPE_TO_PROCESSOR_KEYS = [
        'server.provisioned' => [
            self::CACHE_INVALIDATION_PROCESSOR_KEY,
        ],
        'server.provisioned.v1' => [
            self::CACHE_INVALIDATION_PROCESSOR_KEY,
        ],
        'server.migrated' => [
            self::CACHE_INVALIDATION_PROCESSOR_KEY,
        ],
        'server.migrated.v1' => [
            self::CACHE_INVALIDATION_PROCESSOR_KEY,
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
