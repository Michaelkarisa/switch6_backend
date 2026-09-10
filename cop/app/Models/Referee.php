<?php

namespace App\Models;

class Referee extends BaseUuidModel
{
    protected $fillable = [
        'name',
        'phone',
        'nationality',
        'profile_url'
    ];

     protected $hidden = [
        'author_id',
    ];
    public function matches()
    {
        return $this->hasMany(MatchModel::class, 'referee_id');
    }
}
