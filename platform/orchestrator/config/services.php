<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
        ],
    ],

    'pterodactyl' => [
        'base_url' => env('PTERODACTYL_BASE_URL'),
        'application_api_key' => env('PTERODACTYL_APPLICATION_API_KEY'),
        'client_api_key' => env('PTERODACTYL_CLIENT_API_KEY'),
        'timeout' => (int) env('PTERODACTYL_TIMEOUT', 15),
    ],

    'provisioning' => [
        'bootstrap_ttl_minutes' => (int) env('PROVISIONING_BOOTSTRAP_TTL_MINUTES', 20),
        'bootstrap_max_ttl_minutes' => (int) env('PROVISIONING_BOOTSTRAP_MAX_TTL_MINUTES', 120),
        'orchestrator_base_url' => env('PROVISIONING_ORCHESTRATOR_BASE_URL'),
        'monitor_archive_url' => env('PROVISIONING_MONITOR_ARCHIVE_URL'),
        'monitor_archive_disk' => env('PROVISIONING_MONITOR_ARCHIVE_DISK', 'provisioning_artifacts'),
        'monitor_archive_path' => env('PROVISIONING_MONITOR_ARCHIVE_PATH', 'node-agent/latest/node-monitor.zip'),
        'monitor_archive_public_url' => env('PROVISIONING_MONITOR_ARCHIVE_PUBLIC_URL', false),
        'monitor_archive_url_ttl_minutes' => (int) env('PROVISIONING_MONITOR_ARCHIVE_URL_TTL_MINUTES', 60),
        'monitor_archive_sha256' => env('PROVISIONING_MONITOR_ARCHIVE_SHA256'),
        'monitor_archive_entrypoint' => env('PROVISIONING_MONITOR_ARCHIVE_ENTRYPOINT', 'main.py'),
        'monitor_script_url' => env('PROVISIONING_MONITOR_SCRIPT_URL'),
        'monitor_script_path' => env('PROVISIONING_MONITOR_SCRIPT_PATH'),
        'wings_binary_url_template' => env(
            'PROVISIONING_WINGS_BINARY_URL_TEMPLATE',
            'https://github.com/pterodactyl/wings/releases/latest/download/wings_linux_%s'
        ),
    ],

    'locations_cache' => [
        'disk' => env('LOCATIONS_CACHE_DISK', 'locations_cache'),
        'path' => env('LOCATIONS_CACHE_PATH', 'locations.json'),
    ],

    'event_bus' => [
        'topics' => [
            'server.ordered.v1' => env('EVENT_BUS_SERVER_ORDERS_TOPIC_ARN'),
        ],
        'server_orders_topic_arn' => env('EVENT_BUS_SERVER_ORDERS_TOPIC_ARN'),
        'server_orders_queue_url' => env('EVENT_BUS_SERVER_ORDERS_QUEUE_URL'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'session_token' => env('AWS_SESSION_TOKEN'),
        'endpoint' => env('AWS_ENDPOINT'),
    ],

];
