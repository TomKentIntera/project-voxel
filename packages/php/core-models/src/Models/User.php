<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Interadigital\CoreModels\Database\Factories\UserFactory;
use Interadigital\CoreModels\Enums\UserRole;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function roleEnum(): ?UserRole
    {
        $role = $this->getAttribute('role');

        if ($role instanceof UserRole) {
            return $role;
        }

        if (! is_string($role)) {
            return null;
        }

        return UserRole::tryFrom($role);
    }

    public function isAdmin(): bool
    {
        return $this->roleEnum() === UserRole::ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->roleEnum() === UserRole::CUSTOMER;
    }

    /**
     * @return HasMany<AuthToken, $this>
     */
    public function authTokens(): HasMany
    {
        return $this->hasMany(AuthToken::class);
    }

    /**
     * @return HasMany<Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'user_id', 'id');
    }

    /**
     * @return HasMany<ServerEvent, $this>
     */
    public function actedServerEvents(): HasMany
    {
        return $this->hasMany(ServerEvent::class, 'actor_id', 'id');
    }

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
