<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Interadigital\CoreModels\Database\Factories\TelemetryNodeSampleFactory;

class TelemetryNodeSample extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'telemetry_node_sample';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'node_id',
        'cpu_pct',
        'iowait_pct',
        'recorded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cpu_pct' => 'float',
            'iowait_pct' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    protected static function newFactory(): Factory
    {
        return TelemetryNodeSampleFactory::new();
    }
}
