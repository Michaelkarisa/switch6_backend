<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\Event;


class AdTargetingService
{
    public function pickBestAd(string $matchId, string $period)
    {
        return Advertisement::where('status', 'active')
            ->where(function ($q) use ($period) {
                $q->where('period', $period)->orWhereNull('period');
            })
            ->get()
            ->sortByDesc(function ($ad) use ($matchId) {
                return $ad->events()
                    ->where('match_id', $matchId)
                    ->count();
            })
            ->first();
    }
}