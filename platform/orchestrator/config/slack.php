<?php

return [
    'channels' => [
        'servers' => env(
            'SLACK_CHANNEL_SERVERS',
            env('SLACK_BOT_USER_DEFAULT_CHANNEL', ''),
        ),
        'orders' => env(
            'SLACK_CHANNEL_ORDERS',
            env('SLACK_BOT_USER_DEFAULT_CHANNEL', ''),
        ),
    ],
];
