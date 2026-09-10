<?php

namespace App\Models;

class League extends BaseUuidModel
{
    protected $fillable = [
        'name',
        'name',
        'type',
        'logo_url',
        'timestamp',
        'short_name',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

 protected $hidden = [
        'author_id',
    ];
    
    public function matches()
    {
        return $this->hasMany(MatchModel::class);
    }
}
