<?php

namespace App\Models;

class Comment extends BaseUuidModel
{
    protected $fillable = [
        'match_id',
        'user_id',
        'comment',
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
