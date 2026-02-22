<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Interadigital\CoreModels\Database\Factories\TelemetryNodeFactory;

class TelemetryNode extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'telemetry_node';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'node_id',
        'cpu_pct',
        'iowait_pct',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cpu_pct' => 'float',
            'iowait_pct' => 'float',
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
        return TelemetryNodeFactory::new();
    }
}
