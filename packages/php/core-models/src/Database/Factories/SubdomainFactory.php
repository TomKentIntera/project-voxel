<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Interadigital\CoreModels\Models\Server;
use Interadigital\CoreModels\Models\Subdomain;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Interadigital\CoreModels\Models\Subdomain>
 */
class SubdomainFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Interadigital\CoreModels\Models\Subdomain>
     */
    protected $model = Subdomain::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'prefix' => strtolower(fake()->bothify('srv####')),
            'domain' => 'intera.gg',
        ];
    }
}
