<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Interadigital\CoreModels\Models\TelemetryServer;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Interadigital\CoreModels\Models\TelemetryServer>
 */
class TelemetryServerFactory extends Factory
{
    /**
     * @var class-string<\Interadigital\CoreModels\Models\TelemetryServer>
     */
    protected $model = TelemetryServer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_id' => $this->faker->unique()->uuid(),
            'node_id' => $this->faker->uuid(),
            'players_online' => $this->faker->numberBetween(0, 300),
            'cpu_pct' => $this->faker->randomFloat(3, 0, 100),
            'io_write_bytes_per_s' => $this->faker->randomFloat(3, 0, 50000000),
        ];
    }
}
