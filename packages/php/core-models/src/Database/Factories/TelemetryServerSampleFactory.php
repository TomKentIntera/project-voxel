<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Interadigital\CoreModels\Models\TelemetryServerSample;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Interadigital\CoreModels\Models\TelemetryServerSample>
 */
class TelemetryServerSampleFactory extends Factory
{
    /**
     * @var class-string<\Interadigital\CoreModels\Models\TelemetryServerSample>
     */
    protected $model = TelemetryServerSample::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_id' => $this->faker->uuid(),
            'node_id' => $this->faker->uuid(),
            'players_online' => $this->faker->numberBetween(0, 300),
            'cpu_pct' => $this->faker->randomFloat(3, 0, 100),
            'io_write_bytes_per_s' => $this->faker->randomFloat(3, 0, 50000000),
            'recorded_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ];
    }
}
