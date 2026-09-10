<?php

namespace App\Models;

class MatchBid extends BaseUuidModel
{
    protected $table = 'match_bids';

    protected $fillable = [
        'match_id',
        'advertisement_id',
        'user_id',
        'payment_id',
        'period',
        'slot_rank',
        'amount',
        'currency',
        'status',
    ];

    protected $casts = [
        'amount'    => 'integer',
        'slot_rank' => 'integer',
    ];

    public function ad()
    {
        return $this->belongsTo(Advertisement::class, 'advertisement_id');
    }

    public function match()
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function scopeForPeriod($query, string $matchId, string $period)
    {
        return $query->where('match_id', $matchId)->where('period', $period);
    }

    public function scopeWon($query)
    {
        return $query->where('status', 'won');
    }
}
