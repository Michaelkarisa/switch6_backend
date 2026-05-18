<?php

namespace App\Models;

class Lineup extends BaseUuidModel
{
    protected $fillable = [
        'match_id',
        'player_id',
        'club_id',
        'position',
        'is_starter',
        'is_starting',
        'minute_in',
        'minute_out',
    ];

    protected $casts = [
        'is_starter' => 'boolean',
        'is_starting' => 'boolean',
        'minute_in' => 'integer',
        'minute_out' => 'integer',
    ];

    public function match()
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
