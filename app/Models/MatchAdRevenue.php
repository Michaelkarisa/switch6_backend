<?php

namespace App\Models;

class MatchAdRevenue extends BaseUuidModel
{
    protected $table = 'match_ad_revenues';

    protected $fillable = [
        'match_id',
        'broadcaster_id',
        'period',
        'gross_amount',
        'broadcaster_amount',
        'platform_amount',
        'share_percent',
        'currency',
    ];

    protected $casts = [
        'gross_amount'       => 'integer',
        'broadcaster_amount' => 'integer',
        'platform_amount'    => 'integer',
        'share_percent'      => 'float',
    ];

    public function match()
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }

    public function broadcaster()
    {
        return $this->belongsTo(User::class, 'broadcaster_id');
    }
}
