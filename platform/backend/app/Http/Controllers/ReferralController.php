<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Referral\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Interadigital\CoreModels\Models\User;

class ReferralController extends Controller
{
    public function me(Request $request, ReferralService $referralService): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        return response()->json([
            'referral' => $referralService->summaryForUser($user),
        ]);
    }
}
