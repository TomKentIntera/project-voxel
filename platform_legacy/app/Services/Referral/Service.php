<?php

namespace App\Services\Referral;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

use Illuminate\Support\Str;
use Auth;

use App\Models\User;
use App\Models\ReferralTransaction;
use App\Models\Server;

use App\Services\Log\Service as LogService;
use App\Services\Stripe\Service as StripeService;

class Service
{
    
    private LogService $logger;
    private StripeService $stripe;

    public function __construct(
        LogService $logger,
        StripeService $stripe,
    ) {
        $this->logger = $logger;
        $this->stripe = $stripe;
    }

    public function recalculateReferralBalanceForUser(User $user): void 
    {
        $referralTotal = ReferralTransaction::where('user_id', $user->id)->sum('amount');

        $user->referral_total = $referralTotal;
        $user->save();
    }

    public function triggerReferralBonusForUser(User $user): void
    {
        
    }    
    
}