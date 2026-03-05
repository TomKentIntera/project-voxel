<?php

declare(strict_types=1);

namespace App\Services\Referral;

use App\Services\Stripe\Helpers\StripeClientFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Interadigital\CoreModels\Models\ReferralCode;
use Interadigital\CoreModels\Models\ReferralTransaction;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\User;
use Throwable;

class ReferralService
{
    public function __construct(
        private readonly StripeClientFactory $stripeClientFactory
    ) {
    }

    public function createReferralCodeForUser(User $user): ReferralCode
    {
        $referralCode = ReferralCode::query()->create([
            'referral_code' => $this->generateUniqueReferralCode(),
            'user_id' => (int) $user->id,
            'discount_percent' => $this->defaultDiscountPercent(),
            'referral_percent' => $this->defaultReferralPercent(),
            'valid_for_invoice_count' => $this->defaultInvoiceMonths(),
            'stripe_coupon_code' => null,
        ]);

        return $this->ensureStripePromotionCode($referralCode);
    }

    public function getOrCreateReferralCodeForUser(User $user): ReferralCode
    {
        $referralCode = $user->referralCodes()->orderByDesc('id')->first();

        if (! $referralCode instanceof ReferralCode) {
            return $this->createReferralCodeForUser($user);
        }

        return $this->ensureStripePromotionCode($referralCode);
    }

    /**
     * @return array<string, mixed>
     */
    public function summaryForUser(User $user): array
    {
        $referralCode = $this->getOrCreateReferralCodeForUser($user);
        $periodDays = max(1, (int) config('referral.ledger_period_days', 90));
        $periodStart = now()->subDays($periodDays);

        $ledgerEntries = ReferralTransaction::query()
            ->where('user_id', (int) $user->id)
            ->where('created_at', '>=', $periodStart)
            ->with(['server.user'])
            ->orderByDesc('created_at')
            ->get();

        $earnedLastPeriod = (float) $ledgerEntries->sum('amount');
        $earnedAllTime = (float) ReferralTransaction::query()
            ->where('user_id', (int) $user->id)
            ->sum('amount');
        $revenueLast30Days = (float) ReferralTransaction::query()
            ->where('user_id', (int) $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');
        $referredUserServersCount = (int) ReferralTransaction::query()
            ->where('user_id', (int) $user->id)
            ->distinct('server_id')
            ->count('server_id');
        $referredUsersCount = Server::query()
            ->whereIn(
                'id',
                ReferralTransaction::query()
                    ->where('user_id', (int) $user->id)
                    ->select('server_id')
            )
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        return [
            'code' => (string) $referralCode->referral_code,
            'link' => $this->inviteLink((string) $referralCode->referral_code),
            'discount_percent' => (int) $referralCode->discount_percent,
            'referral_percent' => (int) $referralCode->referral_percent,
            'valid_for_invoice_count' => max(1, (int) ($referralCode->valid_for_invoice_count ?? $this->defaultInvoiceMonths())),
            'referred_users_count' => $referredUsersCount,
            'referred_user_servers_count' => $referredUserServersCount,
            'revenue_last_30_days' => round($revenueLast30Days, 2),
            'earned_last_period' => round($earnedLastPeriod, 2),
            'earned_all_time' => round($earnedAllTime, 2),
            'period_days' => $periodDays,
            'ledger' => $this->transformLedgerEntries($ledgerEntries),
        ];
    }

    public function findByCode(string $referralCode): ?ReferralCode
    {
        return ReferralCode::query()
            ->whereRaw('LOWER(referral_code) = ?', [strtolower(trim($referralCode))])
            ->first();
    }

    public function ensureStripePromotionCode(ReferralCode $referralCode): ReferralCode
    {
        if (! is_string($referralCode->stripe_coupon_code) || $referralCode->stripe_coupon_code === '') {
            $promotionCode = $this->createPromotionCodeForReferral($referralCode);
            if ($promotionCode !== null) {
                $referralCode->stripe_coupon_code = $promotionCode;
                $referralCode->save();
            }
        }

        return $referralCode;
    }

    private function defaultDiscountPercent(): int
    {
        return max(1, min(100, (int) config('referral.default_discount_percent', 10)));
    }

    private function defaultReferralPercent(): int
    {
        return max(1, min(100, (int) config('referral.default_referral_percent', 10)));
    }

    private function defaultInvoiceMonths(): int
    {
        return max(1, (int) config('referral.default_invoice_months', 3));
    }

    private function generateUniqueReferralCode(): string
    {
        do {
            $candidate = Str::upper(Str::random(8));
        } while (ReferralCode::query()->where('referral_code', $candidate)->exists());

        return $candidate;
    }

    private function stripeConfigured(): bool
    {
        $secret = (string) config('services.stripe.secret', '');

        return trim($secret) !== '';
    }

    private function createPromotionCodeForReferral(ReferralCode $referralCode): ?string
    {
        if (! $this->stripeConfigured()) {
            return null;
        }

        try {
            $client = $this->stripeClientFactory->make();
            $discountPercent = (int) ($referralCode->discount_percent ?? $this->defaultDiscountPercent());
            $invoiceMonths = max(1, (int) ($referralCode->valid_for_invoice_count ?? $this->defaultInvoiceMonths()));
            $couponId = $this->resolveOrCreateCouponId($discountPercent, $invoiceMonths);
            $existing = $client->promotionCodes->all([
                'code' => (string) $referralCode->referral_code,
                'limit' => 1,
            ]);

            if (count($existing->data) > 0 && is_string($existing->data[0]->id ?? null)) {
                return $existing->data[0]->id;
            }

            $promotionCode = $client->promotionCodes->create([
                'coupon' => $couponId,
                'code' => (string) $referralCode->referral_code,
            ]);

            return is_string($promotionCode->id ?? null) ? $promotionCode->id : null;
        } catch (Throwable $exception) {
            Log::warning('Unable to create Stripe promotion code for referral.', [
                'referral_code_id' => $referralCode->id,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function resolveOrCreateCouponId(int $discountPercent, int $invoiceMonths): string
    {
        $client = $this->stripeClientFactory->make();
        $coupons = $client->coupons->all(['limit' => 100]);

        foreach ($coupons->data as $coupon) {
            if (
                (string) ($coupon->duration ?? '') === 'repeating'
                && (int) ($coupon->duration_in_months ?? 0) === $invoiceMonths
                && (float) ($coupon->percent_off ?? -1) === (float) $discountPercent
            ) {
                return (string) $coupon->id;
            }
        }

        $coupon = $client->coupons->create([
            'percent_off' => (float) $discountPercent,
            'name' => sprintf('%d%% for first %d invoice months', $discountPercent, $invoiceMonths),
            'duration' => 'repeating',
            'duration_in_months' => $invoiceMonths,
        ]);

        return (string) $coupon->id;
    }

    /**
     * @param Collection<int, ReferralTransaction> $entries
     * @return list<array<string, mixed>>
     */
    private function transformLedgerEntries(Collection $entries): array
    {
        return $entries->map(static function (ReferralTransaction $entry): array {
            $server = $entry->server;
            $referredUser = $server?->user;

            return [
                'id' => (int) $entry->id,
                'amount' => round((float) $entry->amount, 2),
                'created_at' => $entry->created_at?->toIso8601String(),
                'server_uuid' => is_string($server?->uuid) ? $server->uuid : null,
                'plan_purchased' => is_string($server?->plan) ? $server->plan : null,
                'from_user' => $referredUser ? [
                    'id' => (int) $referredUser->id,
                    'name' => (string) $referredUser->name,
                    'email' => (string) $referredUser->email,
                ] : null,
            ];
        })->values()->all();
    }

    private function inviteLink(string $code): string
    {
        $frontend = rtrim((string) env('FRONTEND_URL', (string) config('app.url', 'http://localhost')), '/');

        return $frontend.'/invite/'.$code;
    }
}
