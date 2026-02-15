<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

use App\Models\User;
use Config;

class Server extends Model
{
    use HasFactory;

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
    
    /*
    * Get the user that owns the Server
    *
    * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function referralcode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class, 'referral_id', 'id');
    }

    public function data() : Attribute {
        return Attribute::make(
            get: fn ($value, $attributes) => json_decode($attributes['config']),
        );
    }

    public function plandata() : Attribute {
        return Attribute::make(
            get: fn ($value, $attributes) => $this->get_plan($attributes['plan']),
        );
    }

    function get_plan($plan) {
        $planEntry = null;

        foreach(Config::get('plans.planList') as $planE) {
            if($planE['name'] == $plan) {
                $planEntry = $planE;
            }
        }

        return $planEntry;
    }

}
