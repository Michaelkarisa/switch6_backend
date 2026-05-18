<?php

namespace App\Models;

use App\Models\Event;

class AdPlaybackEvent extends BaseUuidModel
{
    protected $fillable = [
        'advertisement_id',
        'match_id',
        'stream_id',
        'platform',
        'play_time',
        'viewer_count',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];
}