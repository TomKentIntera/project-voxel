<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LegacyDomainController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'source' => config('migration.legacy_app'),
            'domains' => config('migration.domains', []),
        ]);
    }
}
