<?php

namespace App\Services;

use App\Models\MatchAdRevenue;
use App\Models\MatchBid;
use App\Models\MatchModel;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Computes and exposes broadcaster ad-revenue splits per match/period,
 * backing the broadcaster "Revenue" tab (how a match performs and how
 * much they expect to get paid). Currently only attributes revenue from
 * won, paid bid-tab slots — general-campaign revenue isn't tied to a
 * single match unambiguously, so it isn't split here yet.
 */
class MatchRevenueService
{
    /**
     * Recompute (upsert) the revenue split for every period of a match
     * that has resolved (won) bid slots. Call after resolveSlots().
     */
    public function recomputeForMatch(MatchModel $match): Collection
    {
        if (! config('ads.revenue_sharing_enabled', true)) {
            return collect();
        }

        $sharePercent = (float) config('ads.broadcaster_revenue_share_percent', 50);
        $results = collect();

        foreach (['before_match', 'halftime', 'fulltime'] as $period) {
            $gross = (int) MatchBid::forPeriod($match->id, $period)->won()->sum('amount');

            if ($gross <= 0) {
                continue;
            }

            $broadcasterAmount = (int) round($gross * $sharePercent / 100);
            $platformAmount = $gross - $broadcasterAmount;

            $results->push(MatchAdRevenue::updateOrCreate(
                ['match_id' => $match->id, 'period' => $period],
                [
                    'broadcaster_id'     => $match->author_id,
                    'gross_amount'       => $gross,
                    'broadcaster_amount' => $broadcasterAmount,
                    'platform_amount'    => $platformAmount,
                    'share_percent'      => $sharePercent,
                ]
            ));
        }

        return $results;
    }

    /** Revenue tab: every match a broadcaster has earned (or could earn) from. */
    public function forBroadcaster(User $user): Collection
    {
        return MatchAdRevenue::with(['match.homeClub', 'match.awayClub'])
            ->where('broadcaster_id', $user->id)
            ->latest()
            ->get();
    }

    public function summaryForBroadcaster(User $user): array
    {
        $rows = $this->forBroadcaster($user);

        return [
            'enabled'      => config('ads.revenue_sharing_enabled', true),
            'total_gross'  => $rows->sum('gross_amount'),
            'total_earned' => $rows->sum('broadcaster_amount'),
            'matches'      => $rows,
        ];
    }

    public function forMatch(MatchModel $match): Collection
    {
        return MatchAdRevenue::where('match_id', $match->id)->get();
    }
}
