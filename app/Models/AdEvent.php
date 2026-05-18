<?php

namespace App\Models;

use App\Models\Event;

class AdEvent extends BaseUuidModel
{
    protected $fillable = [
        'match_id',
        'advertisement_id',
        'event_type', // injected | played | completed
        'match_minute',
        'period',
        'stream_id',
        'platform',
        'duration_played',
    ];

    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }
}