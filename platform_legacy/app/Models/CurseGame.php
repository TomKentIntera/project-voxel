<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\CurseGameVersion;
use App\Models\CureGameMod;

class CurseGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'curseId',
        'curseSlug'
    ];

    /**
     * Get all of the versions for the CurseGame
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function versions(): HasMany
    {
        return $this->hasMany(CurseGameVersion::class, 'gameId', 'id');
    }

    /**
     * Get all of the mods for the CurseGame
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mods(): HasMany
    {
        return $this->hasMany(CureGameMod::class, 'gameId', 'id');
    }
}
