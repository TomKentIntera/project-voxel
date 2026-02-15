<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurseModVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'curseId',
        'modId',
        'gameId',
        'gameVersion',
        'available',
        'name',
        'fileName',
    ];

    /**
     * Get the mod that owns the CurseModVersion.
     */
    public function mod(): BelongsTo
    {
        return $this->belongsTo(CurseGameMod::class, 'modId', 'id');
    }

    /**
     * Get the game that owns the CurseModVersion.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(CurseGame::class, 'gameId', 'id');
    }
}
