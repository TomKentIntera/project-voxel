<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'last_name',
        'pterodactyl_panel_id',
        'currency',
        'referral_total',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * Get all of the servers for the User.
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'user_id', 'id');
    }

    /**
     * Get all of the referral codes for the User.
     */
    public function referralCodes(): HasMany
    {
        return $this->hasMany(ReferralCode::class, 'user_id', 'id');
    }

    public function referralTransactions(): HasMany
    {
        return $this->hasMany(ReferralTransaction::class, 'user_id', 'id');
    }
}
