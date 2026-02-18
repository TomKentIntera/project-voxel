<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Interadigital\CoreModels\Enums\ServerEventType;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\ServerEvent;
use Interadigital\CoreModels\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Interadigital\CoreModels\Models\ServerEvent>
 */
class ServerEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Interadigital\CoreModels\Models\ServerEvent>
     */
    protected $model = ServerEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'actor_id' => User::factory(),
            'type' => fake()->randomElement(ServerEventType::cases())->value,
            'meta' => [
                'source' => fake()->randomElement(['api', 'dashboard', 'system']),
            ],
        ];
    }

    /**
     * Indicate that the event was created by the system.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'actor_id' => null,
        ]);
    }
}
