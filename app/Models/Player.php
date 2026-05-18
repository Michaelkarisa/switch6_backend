<?php

namespace App\Models;

class Player extends BaseUuidModel
{
    protected $fillable = [
        'name',
        'club_id',
        'position',
        'age',
        'nationality',
        'jersey_number',
        'market_value',
    ];

    protected $casts = [
        'age' => 'integer',
        'jersey_number' => 'integer',
        'market_value' => 'decimal:2',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function lineups()
    {
        return $this->hasMany(Lineup::class);
    }

    public function goals()
    {
        return $this->hasMany(Scorer::class, 'player_id');
    }

    public function assists()
    {
        return $this->hasMany(Scorer::class, 'assist_player_id');
    }
}
