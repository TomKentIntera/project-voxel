<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class FaqController extends Controller
{
    /**
     * Return all FAQs, optionally filtered by homepage visibility.
     *
     * @OA\Get(
     *     path="/api/faqs",
     *     operationId="getFaqs",
     *     tags={"FAQs"},
     *     summary="List frequently asked questions",
     *     @OA\Parameter(
     *         name="homepage_only",
     *         in="query",
     *         required=false,
     *         description="If true, only include FAQs flagged for homepage display.",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FAQ list",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"faqs"},
     *             @OA\Property(
     *                 property="faqs",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/FaqItem")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $faqs = collect(config('faqs'));

        if ($request->boolean('homepage_only')) {
            $faqs = $faqs->filter(fn (array $faq): bool => $faq['showOnHome'] === true);
        }

        $faqs = $faqs->map(function (array $faq): array {
            return [
                'title' => $faq['title'],
                'content' => $faq['content'],
                'showOnHome' => $faq['showOnHome'],
            ];
        })->values()->all();

        return response()->json([
            'faqs' => $faqs,
        ]);
    }
}

