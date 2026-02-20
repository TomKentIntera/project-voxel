<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Interadigital\CoreModels\Models\RegionalProxy;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Interadigital\CoreModels\Models\RegionalProxy>
 */
class RegionalProxyFactory extends Factory
{
    /**
     * @var class-string<\Interadigital\CoreModels\Models\RegionalProxy>
     */
    protected $model = RegionalProxy::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rawToken = RegionalProxy::generateToken();

        return [
            'name' => $this->faker->unique()->words(2, true).' proxy',
            'region' => $this->faker->randomElement(['eu.de', 'us.east', 'us.west', 'ap.southeast']),
            'token_hash' => RegionalProxy::hashToken($rawToken),
            'last_used_at' => null,
        ];
    }
}

