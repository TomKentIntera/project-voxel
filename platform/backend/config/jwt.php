<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret
    |--------------------------------------------------------------------------
    |
    | This secret signs all JWTs issued by the application. Set this in
    | production using an unpredictable value.
    |
    */
    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Time To Live (minutes)
    |--------------------------------------------------------------------------
    |
    | Defines how long access tokens remain valid before expiring.
    |
    */
    'ttl' => (int) env('JWT_TTL', 60 * 24 * 7),

    /*
    |--------------------------------------------------------------------------
    | JWT Refresh Token Time To Live (minutes)
    |--------------------------------------------------------------------------
    |
    | Defines how long refresh tokens remain valid. Refresh tokens are used
    | to obtain a new access token once the original expires.
    |
    */
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 60 * 24 * 30),
];
