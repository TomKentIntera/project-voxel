<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurseGameVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'curseId',
        'gameId',
    ];

    /**
     * Get the game that owns the CurseGameVersion.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(CurseGame::class, 'gameId', 'id');
    }
}
