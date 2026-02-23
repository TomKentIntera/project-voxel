<?php

declare(strict_types=1);

return [
    'resource_consumption' => [
        'cache_store' => env('METRICS_RESOURCE_CONSUMPTION_CACHE_STORE', 'redis'),
        'cache_key' => env('METRICS_RESOURCE_CONSUMPTION_CACHE_KEY', 'metrics:resource-consumption:last-hour'),
        'cache_ttl_minutes' => (int) env('METRICS_RESOURCE_CONSUMPTION_CACHE_TTL_MINUTES', 15),
    ],
];
