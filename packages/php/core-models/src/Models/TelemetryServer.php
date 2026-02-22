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
     * @var string
     */
    protected $primaryKey = 'server_id';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    protected $keyType = 'string';

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
     * @return BelongsTo<TelemetryNode, $this>
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(TelemetryNode::class, 'node_id', 'node_id');
    }

    protected static function newFactory(): Factory
    {
        return TelemetryServerFactory::new();
    }
}
