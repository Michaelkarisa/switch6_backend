<?php

namespace App\Models;

class Referee extends BaseUuidModel
{
    protected $fillable = [
        'name',
        'phone',
        'nationality',
    ];

    public function matches()
    {
        return $this->hasMany(MatchModel::class, 'referee_id');
    }
}
