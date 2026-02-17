<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Interadigital\CoreModels\Models\ReferralCode;
use Interadigital\CoreModels\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Interadigital\CoreModels\Models\ReferralCode>
 */
class ReferralCodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Interadigital\CoreModels\Models\ReferralCode>
     */
    protected $model = ReferralCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'referral_code' => strtoupper(fake()->lexify('invite????')),
            'user_id' => User::factory(),
            'discount_percent' => 50,
            'referral_percent' => 15,
            'stripe_coupon_code' => null,
        ];
    }
}
