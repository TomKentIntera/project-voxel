<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Interadigital\CoreModels\Models\TelemetryNodeSample;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Interadigital\CoreModels\Models\TelemetryNodeSample>
 */
class TelemetryNodeSampleFactory extends Factory
{
    /**
     * @var class-string<\Interadigital\CoreModels\Models\TelemetryNodeSample>
     */
    protected $model = TelemetryNodeSample::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'node_id' => $this->faker->uuid(),
            'cpu_pct' => $this->faker->randomFloat(3, 0, 100),
            'iowait_pct' => $this->faker->randomFloat(3, 0, 100),
            'recorded_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ];
    }
}
