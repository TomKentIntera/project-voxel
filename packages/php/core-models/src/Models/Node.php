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
        'name',
        'region',
        'ip_address',
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
