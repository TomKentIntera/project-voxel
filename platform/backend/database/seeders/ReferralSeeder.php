<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Interadigital\CoreModels\Enums\ServerStatus;
use Interadigital\CoreModels\Enums\UserRole;
use Interadigital\CoreModels\Models\ReferralCode;
use Interadigital\CoreModels\Models\ReferralTransaction;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\User;

class ReferralSeeder extends Seeder
{
    public function run(): void
    {
        $referrers = User::query()
            ->whereIn('email', ['test@test.com', 'admin@test.com'])
            ->orderBy('id')
            ->get();

        if ($referrers->isEmpty()) {
            $fallback = User::query()->where('role', UserRole::CUSTOMER->value)->orderBy('id')->first();
            if ($fallback instanceof User) {
                $referrers = collect([$fallback]);
            }
        }

        if ($referrers->isEmpty()) {
            return;
        }

        $invoiceMonths = max(1, (int) config('referral.default_invoice_months', 3));
        $discountPercent = max(1, min(100, (int) config('referral.default_discount_percent', 10)));
        $referralPercent = max(1, min(100, (int) config('referral.default_referral_percent', 10)));

        foreach ($referrers as $referrer) {
            if (! $referrer instanceof User) {
                continue;
            }

            $referralCode = $this->getOrCreateActiveReferralCode(
                $referrer,
                $discountPercent,
                $referralPercent,
                $invoiceMonths
            );

            $referrerSlug = Str::slug((string) Str::before((string) $referrer->email, '@'));

            // Demo scenario per referrer: 3 referred users with up to 3 invoices per server.
            for ($userIndex = 1; $userIndex <= 3; $userIndex++) {
                $referredUser = User::query()->updateOrCreate(
                    ['email' => sprintf('referred%d.%s@test.com', $userIndex, $referrerSlug)],
                    [
                        'username' => sprintf('ref%d_%s', $userIndex, $referrerSlug),
                        'first_name' => 'Referred',
                        'last_name' => 'User '.$userIndex,
                        'name' => 'Referred User '.$userIndex,
                        'password' => Hash::make((string) env('SEED_USER_PASSWORD', 'password')),
                        'role' => UserRole::CUSTOMER->value,
                    ]
                );

                // User 1 -> 1 server, user 2 -> 2 servers, user 3 -> 3 servers.
                $serverCount = $userIndex;
                for ($serverIndex = 1; $serverIndex <= $serverCount; $serverIndex++) {
                    $server = Server::query()->updateOrCreate(
                        [
                            'stripe_tx_id' => sprintf(
                                'seed_ref_sub_%s_u%d_s%d',
                                $referrerSlug,
                                $userIndex,
                                $serverIndex
                            ),
                        ],
                        [
                            'uuid' => sprintf('seed-ref-%s-u%d-s%d', $referrerSlug, $userIndex, $serverIndex),
                            'user_id' => (int) $referredUser->id,
                            'plan' => $this->planForIndex($serverIndex),
                            'config' => json_encode([
                                'name' => sprintf(
                                    'Seed Referral Server %s %d-%d',
                                    strtoupper($referrerSlug),
                                    $userIndex,
                                    $serverIndex
                                ),
                                'location' => 'de',
                                'minecraft_version' => '1.21.4',
                                'type' => 'paper',
                                'type_version' => null,
                            ]),
                            'initialised' => true,
                            'stripe_tx_return' => true,
                            'suspended' => false,
                            'status' => ServerStatus::ACTIVE->value,
                            'ptero_id' => 0,
                            'referral_id' => (int) $referralCode->id,
                            'referral_paid' => false,
                            'days_before_cancellation' => 0,
                        ]
                    );

                    $invoiceCount = min($invoiceMonths, $serverIndex + 1);
                    for ($invoiceIndex = 1; $invoiceIndex <= $invoiceCount; $invoiceIndex++) {
                        $amount = round((20 + ($serverIndex * 5)) * ($referralPercent / 100), 2);
                        $createdAt = Carbon::now()->subDays(($userIndex * 7) + ($invoiceIndex * 3));

                        ReferralTransaction::query()->updateOrCreate(
                            [
                                'server_id' => (int) $server->id,
                                'stripe_invoice_id' => sprintf(
                                    'in_seed_ref_%s_u%d_s%d_i%d',
                                    $referrerSlug,
                                    $userIndex,
                                    $serverIndex,
                                    $invoiceIndex
                                ),
                            ],
                            [
                                'user_id' => (int) $referrer->id,
                                'referral_id' => (int) $referralCode->id,
                                'amount' => $amount,
                                'created_at' => $createdAt,
                                'updated_at' => $createdAt,
                            ]
                        );
                    }

                    $creditedInvoices = ReferralTransaction::query()
                        ->where('server_id', (int) $server->id)
                        ->count();

                    $server->referral_paid = $creditedInvoices >= $invoiceMonths;
                    $server->save();
                }
            }
        }
    }

    private function getOrCreateActiveReferralCode(
        User $referrer,
        int $discountPercent,
        int $referralPercent,
        int $invoiceMonths
    ): ReferralCode {
        $existing = $referrer->referralCodes()->orderByDesc('id')->first();

        if ($existing instanceof ReferralCode) {
            $existing->discount_percent = $discountPercent;
            $existing->referral_percent = $referralPercent;
            $existing->valid_for_invoice_count = $invoiceMonths;
            $existing->save();

            return $existing;
        }

        return ReferralCode::query()->create([
            'referral_code' => $this->generateUniqueReferralCode($referrer),
            'user_id' => (int) $referrer->id,
            'discount_percent' => $discountPercent,
            'referral_percent' => $referralPercent,
            'valid_for_invoice_count' => $invoiceMonths,
            'stripe_coupon_code' => null,
        ]);
    }

    private function planForIndex(int $index): string
    {
        $plans = array_values(array_filter(
            (array) config('plans.planList', []),
            static fn (mixed $plan): bool => is_array($plan) && is_string($plan['name'] ?? null)
        ));

        if ($plans === []) {
            return 'panda';
        }

        $safeIndex = max(0, min(count($plans) - 1, $index - 1));

        return (string) $plans[$safeIndex]['name'];
    }

    private function generateUniqueReferralCode(User $referrer): string
    {
        $base = strtoupper('REF'.$referrer->id.'SEED');
        $candidate = substr($base, 0, 12);

        if (! ReferralCode::query()->where('referral_code', $candidate)->exists()) {
            return $candidate;
        }

        do {
            $candidate = strtoupper('REF'.$referrer->id.Str::random(6));
            $candidate = substr($candidate, 0, 12);
        } while (ReferralCode::query()->where('referral_code', $candidate)->exists());

        return $candidate;
    }
}
