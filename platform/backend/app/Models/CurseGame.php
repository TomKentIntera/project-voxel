<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurseGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'curseId',
        'curseSlug',
    ];

    /**
     * Get all of the versions for the CurseGame.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(CurseGameVersion::class, 'gameId', 'id');
    }

    /**
     * Get all of the mods for the CurseGame.
     */
    public function mods(): HasMany
    {
        return $this->hasMany(CurseGameMod::class, 'gameId', 'id');
    }
}
