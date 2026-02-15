<?php

namespace App\Services\ReferralCode;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

use Illuminate\Support\Str;
use Auth;

use App\Models\ReferralCode;

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

    public function create($userId = null) {
        if($userId == null) {
            if(Auth::guest()) {
                return null;
            }
            $userId = Auth::user()->id;
        }

        return ReferralCode::create([
            'referral_code' => Str::upper(Str::random(8)),
            'user_id' => $userId,
            'discount_percent' => Config::get('referral.defaultDiscountPercent'),
            'referral_percent' => Config::get('referral.defaultReferralPercent')
        ]);
    }

    public function createWithPromoCode($userId = null) {
        $referralCode = $this->create($userId);

        $discountPercent = $referralCode->discount_percent;

        if($discountPercent == null) {
            $discountPercent = Config::get('referral.defaultDiscountPercent');
        }

        $coupon = $this->stripe->getOrCreateCoupon($discountPercent);
        $promoCode = $this->stripe->createPromoCode($referralCode->referral_code, $coupon->id);

        $referralCode->stripe_coupon_code = $promoCode->id;
        $referralCode->save();

        return $referralCode;
    }

    public function getLatestLink($user = null) {
        if($user == null && Auth::guest()) {
            return null;
        }
        if($user == null) {
            $user = Auth::user();
        }

        $latestCode = $user->referralCodes->sortByDesc('id')->first();

        if($latestCode == null) {
            $this->createWithPromoCode($user->id);
            $latestCode = $user->referralCodes->sortByDesc('id')->first();

        }

        return $latestCode->getLink();


    }
    
}