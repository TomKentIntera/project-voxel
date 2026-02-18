<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Return all FAQs, optionally filtered by homepage visibility.
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

