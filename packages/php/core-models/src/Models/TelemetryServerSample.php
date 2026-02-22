<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Interadigital\CoreModels\Database\Factories\TelemetryServerSampleFactory;

class TelemetryServerSample extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'telemetry_server_sample';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'server_id',
        'node_id',
        'players_online',
        'cpu_pct',
        'io_write_bytes_per_s',
        'recorded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'players_online' => 'integer',
            'cpu_pct' => 'float',
            'io_write_bytes_per_s' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    protected static function newFactory(): Factory
    {
        return TelemetryServerSampleFactory::new();
    }
}
