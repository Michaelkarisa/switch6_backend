<?php

namespace App\Models;

class Scorer extends BaseAppendOnlyModel
{
    protected $fillable = [
        'match_id',
        'player_id',
        'club_id',
        'minute',
        'goal_type',
        'assist_player_id',
    ];

    protected $casts = [
        'minute' => 'integer',
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

    public function assistPlayer()
    {
        return $this->belongsTo(Player::class, 'assist_player_id');
    }
}
