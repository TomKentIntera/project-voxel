<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

use App\Models\CurseGame;

class CurseGameVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'curseId',
        'gameId'
    ];

    /**
     * Get the game that owns the CurseGameVersion
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(CurseGame::class, 'gameId', 'id');
    }
}
