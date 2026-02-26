<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Stripe\Services\StripeBillingPortalSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Interadigital\CoreModels\Models\User;

class BillingController extends Controller
{
    /**
     * Create a Stripe Billing Portal session for the authenticated user.
     */
    public function portalSession(
        Request $request,
        StripeBillingPortalSessionService $billingPortalSessionService
    ): JsonResponse {
        $user = $request->user();
        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        try {
            $portalUrl = $billingPortalSessionService->createCustomerPortalUrl($user);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'portal_url' => $portalUrl,
        ]);
    }
}

