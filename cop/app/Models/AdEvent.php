<?php

namespace App\Models;

class AdEvent extends BaseAppendOnlyModel
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
        'view_count'
    ];

    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }
}
