<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Interadigital\CoreModels\Models\Node;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Interadigital\CoreModels\Models\Node>
 */
class NodeFactory extends Factory
{
    /**
     * @var class-string<\Interadigital\CoreModels\Models\Node>
     */
    protected $model = Node::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rawToken = Node::generateToken();

        return [
            'id' => (string) Str::uuid(),
            'name' => $this->faker->unique()->words(2, true).' node',
            'region' => $this->faker->randomElement(['eu.de', 'us.east', 'us.west', 'ap.southeast']),
            'ip_address' => $this->faker->ipv4(),
            'token_hash' => Node::hashToken($rawToken),
            'last_active_at' => null,
            'last_used_at' => null,
        ];
    }
}
