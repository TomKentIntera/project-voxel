<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Interadigital\CoreModels\Models\TelemetryNode;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Interadigital\CoreModels\Models\TelemetryNode>
 */
class TelemetryNodeFactory extends Factory
{
    /**
     * @var class-string<\Interadigital\CoreModels\Models\TelemetryNode>
     */
    protected $model = TelemetryNode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'node_id' => $this->faker->unique()->uuid(),
            'cpu_pct' => $this->faker->randomFloat(3, 0, 100),
            'iowait_pct' => $this->faker->randomFloat(3, 0, 100),
        ];
    }
}
