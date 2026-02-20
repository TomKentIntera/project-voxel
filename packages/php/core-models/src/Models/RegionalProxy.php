<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Interadigital\CoreModels\Database\Factories\RegionalProxyFactory;

class RegionalProxy extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'region',
        'token_hash',
        'last_active_at',
        'last_used_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
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

    /**
     * Generate a raw token suitable for regional proxy authentication.
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
     * Determine whether the supplied raw token matches this proxy.
     */
    public function matchesToken(string $rawToken): bool
    {
        return hash_equals($this->token_hash, self::hashToken($rawToken));
    }

    protected static function newFactory(): Factory
    {
        return RegionalProxyFactory::new();
    }
}

