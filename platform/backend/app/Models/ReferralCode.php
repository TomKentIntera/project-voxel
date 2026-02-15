<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_code',
        'user_id',
        'discount_percent',
        'referral_percent',
        'stripe_coupon_code',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'referral_id', 'id');
    }

    public function getLink()
    {
        return url('invite/'.$this->attributes['referral_code']);
    }
}
