<?php

namespace App\Services;

use App\Models\Advertisement;


class AdTargetingService
{
    public function pickBestAd(?string $matchId, ?string $period)
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

    /**
     * Ads eligible to play for a given match+period, specific (bid-won)
     * slots first, then general campaigns filling any remaining slots.
     */
    public function getads(string $matchId, string $period): array
    {
        $specific = \App\Models\MatchBid::with('ad')
            ->forPeriod($matchId, $period)
            ->won()
            ->orderBy('slot_rank')
            ->get()
            ->pluck('ad')
            ->filter(fn ($ad) => $ad && $ad->status === 'active')
            ->values();

        $generalNeeded = max(0, config('ads.slots_per_period', 5) - $specific->count());

        $general = collect();
        if ($generalNeeded > 0) {
            $general = Advertisement::where('status', 'active')
                ->where('campaign_type', 'general')
                ->where(function ($q) use ($period) {
                    $q->where('period', $period)->orWhereNull('period');
                })
                ->inRandomOrder()
                ->limit($generalNeeded)
                ->get();
        }

        return $specific->merge($general)->values()->all();
    }
}