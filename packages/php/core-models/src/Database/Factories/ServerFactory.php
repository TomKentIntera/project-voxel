<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Interadigital\CoreModels\Enums\ServerStatus;
use Interadigital\CoreModels\Models\Server;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Interadigital\CoreModels\Models\Server>
 */
class ServerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Interadigital\CoreModels\Models\Server>
     */
    protected $model = Server::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stripe_tx_id' => 'TEST',
            'config' => 'TEST',
            'plan' => 'panda',
            'uuid' => (string) Str::uuid(),
            'initialised' => 0,
            'stripe_tx_return' => 0,
            'user_id' => 0,
            'suspended' => 0,
            'status' => ServerStatus::NEW->value,
            'ptero_id' => 0,
            'referral_id' => 0,
            'referral_paid' => 0,
            'days_before_cancellation' => 0,
        ];
    }
}
