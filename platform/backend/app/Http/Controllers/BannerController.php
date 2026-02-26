<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class BannerController extends Controller
{
    /**
     * Return the current homepage banner configuration.
     *
     * @OA\Get(
     *     path="/api/banner",
     *     operationId="getBanner",
     *     tags={"Banner"},
     *     summary="Get homepage banner configuration",
     *     @OA\Response(
     *         response=200,
     *         description="Banner configuration",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"visible", "content"},
     *             @OA\Property(property="visible", type="boolean", example=true),
     *             @OA\Property(property="content", type="string", example="Winter sale is live!")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'visible' => (bool) config('banner.visible'),
            'content' => (string) config('banner.content'),
        ]);
    }
}

