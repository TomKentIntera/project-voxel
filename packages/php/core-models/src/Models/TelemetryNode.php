<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Interadigital\CoreModels\Database\Factories\TelemetryNodeFactory;

class TelemetryNode extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'telemetry_node';

    /**
     * @var string
     */
    protected $primaryKey = 'node_id';

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
     * @return HasMany<TelemetryServer, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(TelemetryServer::class, 'node_id', 'node_id');
    }

    protected static function newFactory(): Factory
    {
        return TelemetryNodeFactory::new();
    }
}
