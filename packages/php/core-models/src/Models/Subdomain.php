<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Interadigital\CoreModels\Database\Factories\SubdomainFactory;

class Subdomain extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'server_id',
        'prefix',
        'domain',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id', 'id');
    }

    public function hostname(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?string => isset($attributes['prefix'], $attributes['domain'])
                ? sprintf('%s.%s', (string) $attributes['prefix'], (string) $attributes['domain'])
                : null,
        );
    }

    protected static function newFactory(): Factory
    {
        return SubdomainFactory::new();
    }
}
