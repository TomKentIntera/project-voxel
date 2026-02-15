<?php

return [
    'legacy_app' => env('LEGACY_APP_PATH', realpath(base_path('../platform_legacy')) ?: '../platform_legacy'),

    'domains' => [
        [
            'key' => 'catalog',
            'description' => 'Public marketing pages and plan discovery.',
            'legacyRoutes' => [
                'GET /',
                'GET /plans',
                'GET /faqs',
                'GET /terms',
                'GET /privacy-policy',
                'GET /vaulthunters',
            ],
            'targetApi' => '/api/v1/catalog/*',
            'status' => 'not_started',
        ],
        [
            'key' => 'checkout',
            'description' => 'Plan configuration, purchase and provisioning handoff.',
            'legacyRoutes' => [
                'GET /plan/configure/{plan}',
                'POST /plan/configure/{plan}/do',
                'GET /plan/configure/{plan}/mod/{id}',
                'POST /plan/modded/configure/{plan}/do',
                'GET /plan/purchase/{planUUID}',
                'GET /server/initialise/{serverUUID}',
            ],
            'targetApi' => '/api/v1/checkout/*',
            'status' => 'not_started',
        ],
        [
            'key' => 'customer_portal',
            'description' => 'Authenticated customer dashboard and billing actions.',
            'legacyRoutes' => [
                'GET /client',
                'GET /client/billing',
                'GET /client/referrals',
            ],
            'targetApi' => '/api/v1/customer/*',
            'status' => 'not_started',
        ],
        [
            'key' => 'provisioning_integrations',
            'description' => 'System integration endpoints and asynchronous hooks.',
            'legacyRoutes' => [
                'GET /api/versions/vanilla',
                'GET /api/versions/bungee',
                'GET /api/versions/forge',
                'GET /api/server/isInitialised/{uuid}',
                'POST /api/webhook/stripe',
                'POST /availability',
            ],
            'targetApi' => '/api/v1/integrations/*',
            'status' => 'not_started',
        ],
    ],
];
