<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Interadigital\CoreModels\Database\Factories\TelemetryServerFactory;

class TelemetryServer extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'telemetry_server';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'server_id',
        'node_id',
        'players_online',
        'cpu_pct',
        'io_write_bytes_per_s',
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
        ];
    }

    /**
     * @return BelongsTo<Node, $this>
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id', 'id');
    }

    protected static function newFactory(): Factory
    {
        return TelemetryServerFactory::new();
    }
}
