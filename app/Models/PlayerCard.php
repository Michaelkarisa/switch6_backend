<?php

namespace App\Models;

class PlayerCard extends BaseUuidModel
{
    protected $fillable = [
        'league_id',
        'match_id',
        'player_id',
        'minute',
        'card',
    ];

    protected $casts = [
        'minute' => 'integer',
    ];
   

    public function player()
    {
        return $this->belongsTo(Player::class,'player_id');
    }

    public function league()
    {
        return $this->belongsTo(League::class,'league_id');
    }

    public function match()
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }

}
