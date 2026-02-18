<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Interadigital\CoreModels\Models\AuthToken;
use Interadigital\CoreModels\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Interadigital\CoreModels\Models\AuthToken>
 */
class AuthTokenFactory extends Factory
{
    /**
     * @var class-string<\Interadigital\CoreModels\Models\AuthToken>
     */
    protected $model = AuthToken::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDays(30),
            'revoked_at' => null,
        ];
    }

    /**
     * Mark the token as revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }

    /**
     * Mark the token as expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}

