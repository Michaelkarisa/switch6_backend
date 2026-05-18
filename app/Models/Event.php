<?php

namespace App\Models;

class Event extends BaseUuidModel
{
    protected $fillable = [
        'match_id',
        'advertisement_id',
    ];

    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class, 'advertisement_id', 'advertisement_id');
    }

    public function match()
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }
}
