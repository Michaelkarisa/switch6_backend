<?php

namespace App\Services;

use App\Models\BroadcasterRank;
use Illuminate\Support\Facades\DB;

class BroadcasterRankService
{
    public function findOrDefault(int $broadcasterId): BroadcasterRank|array
    {
        $rank = BroadcasterRank::where('broadcaster_id', $broadcasterId)->first();

        return $rank ?: ['broadcaster_id' => $broadcasterId, 'points' => 0];
    }

    /**
     * Highest cumulative view count across this broadcaster's past sessions.
     */
    public function highestViews(int $broadcasterId): int
    {
        $highest = DB::table('stream_sessions as s')
            ->join('match_views as v', 'v.match_id', '=', 's.match_id')
            ->where('s.broadcaster_id', $broadcasterId)
            ->select(DB::raw('SUM(v.view_count) as total'))
            ->groupBy('s.match_id')
            ->orderByDesc('total')
            ->limit(1)
            ->value('total');


        return (int) ($highest ?? 0);
    }

    /**
     * Award points to a broadcaster based on how the current stream's view
     * count compares to their personal best (current / highest).
     * If there's no prior data (highest == 0), award a flat 1 point.
     */
    public function award(int $broadcasterId, int $currentViews): float
    {
        $highest = $this->highestViews($broadcasterId);

        $point = $highest === 0
            ? 1.0
            : $currentViews / $highest;

        $existing = BroadcasterRank::where('broadcaster_id', $broadcasterId)->first();

        if ($existing) {
            $existing->update([
                'points'     => $existing->points + $point,
                'updated_at' => now(),
            ]);
        } else {
            BroadcasterRank::create([
                'broadcaster_id' => $broadcasterId,
                'points'         => $point,
            ]);
        }

        return $point;
    }

    public function updatePoints(int $broadcasterId, float $points): int
    {
        return BroadcasterRank::where('broadcaster_id', $broadcasterId)
            ->update([
                'points'     => $points,
                'updated_at' => now(),
            ]);
    }

    public function delete(int $broadcasterId): int
    {
        return BroadcasterRank::where('broadcaster_id', $broadcasterId)->delete();
    }
}
