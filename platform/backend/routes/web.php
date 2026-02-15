<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'platform-backend',
        'message' => 'API-first backend for the platform modernization project.',
        'api' => '/api/v1',
    ]);
});
