<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StreamSession extends BaseAppendOnlyModel
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'other_match_id',
        'status',
        'match_period',
        'broadcaster_id',
        'current_streamer',
        'platform_targets',
        'last_activity_at',
        'members',
    ];

    protected $casts = [
        'platform_targets'  => 'array',
        'members'           => 'array',
        'last_activity_at'  => 'datetime',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(StreamEvent::class, 'match_id');
    }

    public function matchViews(): HasMany
    {
        return $this->hasMany(MatchViews::class, 'match_id');
    }
}
