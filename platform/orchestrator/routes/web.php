<?php

use Illuminate\Support\Facades\Route;
use Interadigital\CoreModels\Models\User;

Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'platform-orchestrator',
        'user_model' => User::class,
    ]);
});
