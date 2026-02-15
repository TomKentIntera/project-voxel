<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SystemHealthController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'platform-backend',
            'apiVersion' => 'v1',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
