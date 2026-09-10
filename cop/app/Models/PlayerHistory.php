<?php

namespace App\Models;

class PlayerHistory extends BaseUuidModel
{
    protected $fillable = [
        'author_id',
        'player_id',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'left_at' => 'datetime',
        'joined_at' => 'datetime'
    ];
   

    public function player()
    {
        return $this->belongsTo(Player::class,'player_id');
    }


}
