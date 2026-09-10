<?php

namespace App\Services;

use App\Models\AdEvent;
use App\Models\Advertisement;
use App\Models\AuditLog;

/**
 * Per-advertisement analytics — used by the advertiser/broadcaster
 * analytics tab and by admin's per-ad analytics endpoint.
 */
class AdvertisementAnalyticsService
{
    /**
     * Full analytics summary for a single advertisement.
     * Replaces the old 3-field stub with a rich data set.
     */
    public function summary(string $adId): array
    {
        $events = AdEvent::where('advertisement_id', $adId)->get();

        $impressions = $events->where('event_type', 'injected')->count();
        $plays       = $events->where('event_type', 'played')->count();
        $completed   = $events->where('event_type', 'completed')->count();

        // Play-through rate & completion rate
        $playRate       = $impressions > 0 ? round($plays / $impressions * 100, 1) : 0;
        $completionRate = $plays > 0       ? round($completed / $plays * 100, 1)   : 0;

        // Watch-time
        $totalPlayTimeSecs = $events->where('event_type', 'played')->sum('play_time') ?? 0;
        $avgPlayTimeSecs   = $plays > 0 ? round($totalPlayTimeSecs / $plays, 1) : 0;

        // Viewer reach
        $avgViewers  = round($events->avg('viewer_count') ?? 0);
        $peakViewers = $events->max('viewer_count') ?? 0;

        // By period slot  (e.g. 'Half Time', 'Before 1ST')
        $byPeriod = $events->whereNotNull('period')
            ->groupBy('period')
            ->map(fn ($group) => $group->count())
            ->toArray();
        arsort($byPeriod);

        // By platform  (youtube | facebook | rtmp_custom | …)
        $byPlatform = $events->whereNotNull('platform')
            ->groupBy('platform')
            ->map(fn ($group) => $group->count())
            ->toArray();
        arsort($byPlatform);

        // Events per day (last 30 days of this ad's impressions)
        $perDay = AdEvent::where('advertisement_id', $adId)
            ->where('event_type', 'injected')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'count' => $r->count])
            ->values()
            ->toArray();

        // Events per match (which matches ran this ad most?)
        $byMatch = AdEvent::where('advertisement_id', $adId)
            ->whereNotNull('match_id')
            ->selectRaw('match_id, COUNT(*) as events')
            ->groupBy('match_id')
            ->orderByDesc('events')
            ->limit(10)
            ->pluck('events', 'match_id')
            ->toArray();

        return [
            // Core funnel
            'impressions'         => $impressions,
            'plays'               => $plays,
            'completed'           => $completed,

            // Rates
            'play_rate_pct'       => $playRate,
            'completion_rate_pct' => $completionRate,

            // Watch-time
            'total_play_time'     => $totalPlayTimeSecs,
            'avg_play_time'       => $avgPlayTimeSecs,

            // Audience
            'avg_viewers'         => $avgViewers,
            'peak_viewers'        => $peakViewers,

            // Breakdowns
            'by_period'           => $byPeriod,
            'by_platform'         => $byPlatform,
            'impressions_per_day' => $perDay,
            'by_match'            => $byMatch,
        ];
    }
}