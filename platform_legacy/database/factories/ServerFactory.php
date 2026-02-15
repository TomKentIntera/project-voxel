<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Server>
 */
class ServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'stripe_tx_id' => "TEST",
            'config' => 'TEST',
            'plan' => 'panda',
            'uuid' => Str::uuid(),
            'initialised' => 0,
            'stripe_tx_return' => 0,
            'user_id' => 0,
            'suspended' => 0,
            'status' => 0,
            'ptero_id' => 0,
            'referral_id' => 0,
            'referral_paid' => 0,
            'days_before_cancellation' => 0,
        ];
    }
}
