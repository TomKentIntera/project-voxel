<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurseGameMod extends Model
{
    use HasFactory;

    protected $fillable = [
        'curseModId',
        'gameId',
        'name',
        'slug',
        'summary',
        'curseLink',
        'available',
    ];

    /**
     * Get the game that owns the CurseGameMod.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(CurseGame::class, 'gameId', 'id');
    }

    /**
     * Get all of the files for the CurseGameMod.
     */
    public function files(): HasMany
    {
        return $this->hasMany(CurseModVersion::class, 'modId', 'id');
    }
}
