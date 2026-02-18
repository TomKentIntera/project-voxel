<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    /**
     * Return the current homepage banner configuration.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'visible' => (bool) config('banner.visible'),
            'content' => (string) config('banner.content'),
        ]);
    }
}

