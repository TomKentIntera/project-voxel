<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\Referral\ReferralService;
use Illuminate\Support\Facades\Log;
use Interadigital\CoreModels\Models\User;
use Throwable;

class UserObserver
{
    public function __construct(
        private readonly ReferralService $referralService
    ) {
    }

    public function created(User $user): void
    {
        try {
            $this->referralService->getOrCreateReferralCodeForUser($user);
        } catch (Throwable $exception) {
            Log::warning('Unable to create referral code for new user.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
