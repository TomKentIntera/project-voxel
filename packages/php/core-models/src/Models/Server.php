<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use Interadigital\CoreModels\Database\Factories\ServerFactory;

class Server extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'stripe_tx_id',
        'config',
        'plan',
        'uuid',
        'initialised',
        'stripe_tx_return',
        'user_id',
        'suspended',
        'status',
        'ptero_id',
        'referral_id',
        'referral_paid',
        'days_before_cancellation',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function referralcode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class, 'referral_id', 'id');
    }

    public function data(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): mixed => isset($attributes['config'])
                ? json_decode((string) $attributes['config'])
                : null,
        );
    }

    public function plandata(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?array => isset($attributes['plan'])
                ? $this->get_plan((string) $attributes['plan'])
                : null,
        );
    }

    public function get_plan(string $plan): ?array
    {
        foreach (Config::get('plans.planList', []) as $planEntry) {
            if (is_array($planEntry) && ($planEntry['name'] ?? null) === $plan) {
                return $planEntry;
            }
        }

        return null;
    }

    protected static function newFactory(): Factory
    {
        return ServerFactory::new();
    }
}
