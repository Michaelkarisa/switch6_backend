<?php

namespace App\Models;

class MatchView extends BaseUuidModel
{
    protected $fillable = [
        'match_id',
        'user_id',
        'minute',
        'viewed_at',
    ];

    protected $casts = [
        'minute' => 'integer',
        'viewed_at' => 'datetime',
    ];

    public function match()
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
