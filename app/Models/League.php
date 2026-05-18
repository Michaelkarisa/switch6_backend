<?php

namespace App\Models;

class League extends BaseUuidModel
{
    protected $fillable = [
        'name',
        'leaguename',
        'type',
        'logo',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function matches()
    {
        return $this->hasMany(MatchModel::class);
    }
}
