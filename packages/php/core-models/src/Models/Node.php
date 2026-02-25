<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Interadigital\CoreModels\Database\Factories\NodeFactory;

class Node extends Model
{
    use HasFactory;

    public const SYNC_STATUS_PENDING = 'pending';

    public const SYNC_STATUS_SYNCING = 'syncing';

    public const SYNC_STATUS_SYNCED = 'synced';

    public const SYNC_STATUS_FAILED = 'failed';

    /**
     * @var string
     */
    protected $table = 'nodes';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

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
        'id',
        'ptero_node_id',
        'name',
        'region',
        'ptero_location_id',
        'ip_address',
        'fqdn',
        'scheme',
        'behind_proxy',
        'maintenance_mode',
        'memory',
        'memory_overallocate',
        'disk',
        'disk_overallocate',
        'upload_size',
        'daemon_sftp',
        'daemon_listen',
        'allocation_ip',
        'allocation_alias',
        'allocation_ports',
        'sync_status',
        'sync_error',
        'synced_at',
        'token_hash',
        'last_active_at',
        'last_used_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'token_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ptero_node_id' => 'integer',
            'ptero_location_id' => 'integer',
            'behind_proxy' => 'boolean',
            'maintenance_mode' => 'boolean',
            'memory' => 'integer',
            'memory_overallocate' => 'integer',
            'disk' => 'integer',
            'disk_overallocate' => 'integer',
            'upload_size' => 'integer',
            'daemon_sftp' => 'integer',
            'daemon_listen' => 'integer',
            'allocation_ports' => 'array',
            'synced_at' => 'datetime',
            'last_active_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $node): void {
            if (! is_string($node->id) || trim($node->id) === '') {
                $node->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Generate a raw token suitable for node telemetry authentication.
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Hash a raw token string for storage/lookup.
     */
    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    /**
     * Determine whether the supplied raw token matches this node.
     */
    public function matchesToken(string $rawToken): bool
    {
        return hash_equals($this->token_hash, self::hashToken($rawToken));
    }

    protected static function newFactory(): Factory
    {
        return NodeFactory::new();
    }
}
