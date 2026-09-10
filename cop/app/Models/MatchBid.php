<?php

namespace App\Models;

class MatchBid extends BaseUuidModel
{
    protected $table = 'match_bids';

    protected $fillable = [
        'match_id',
        'advertisement_id',
        'price',
        'currency',
        'slot',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function ad()
    {
        return $this->belongsTo(Advertisement::class, 'advertisement_id');
    }

    public function match()
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }

}
