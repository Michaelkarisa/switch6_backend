<?php

namespace App\Services;

use App\Models\AdEvent;
use App\Models\MatchModel;
use Illuminate\Support\Facades\DB;

/**
 * Per-match analytics for the broadcaster dashboard.
 * Uses only columns confirmed in the migration:
 *   matches:     id, league_id, home_club_id, away_club_id, match_date, home_score, away_score, status
 *   match_views: id, match_id, user_id, minute, viewed_at
 *   ad_events:   id, advertisement_id, match_id, event_type, period, play_time, viewer_count, platform
 */
class MatchAnalyticsService
{
    public function forMatch(MatchModel $match): array
    {

            return [
                'overview'     => $this->overview($match),
                'views'      => $this->viewerMetrics($match),
                'timeline'     => $this->viewerTimeline($match),
                'ads'          => $this->adMetrics($match),
                'ad_timeline'  => $this->adTimeline($match),
                'engagement'   => $this->engagementBreakdown($match),
                'peak_minutes' => $this->peakMinutes($match),
                'device_split' => $this->deviceSplit($match),
            ];
    }

    // ──────────────────────────────────────────────────────────────

    private function overview(MatchModel $match): array
    {
        $totalViews  = DB::table('match_views')->where('match_id', $match->id)->count();
        $adEvents    = AdEvent::where('match_id', $match->id);

        return [
            'match_id'        => $match->id,
            'status'          => $match->status,
            'home_club'       => $match->homeClub?->name,
            'away_club'       => $match->awayClub?->name,
            'league'          => $match->league?->name,
            'match_date'      => $match->match_date?->toDateTimeString(),
            'home_score'      => $match->home_score,
            'away_score'      => $match->away_score,
            'total_views'     => $totalViews,
            'anonymous_views' => $totalViews,
            'total_ad_events' => (clone $adEvents)->count(),
            'ad_impressions'  => (clone $adEvents)->where('event_type', 'injected')->count(),
        ];
    }

    private function viewerMetrics(MatchModel $match): array
    {
        $base = DB::table('match_views')->where('match_id', $match->id);

        // Peak: minute with the most view rows
        $peakRow = (clone $base)->whereNotNull('view_count')
            ->selectRaw('view_count, COUNT(*) as cnt')
            ->groupBy('view_count')
            ->orderByDesc('cnt')
            ->first();

        return [
            'peak_views'           => $peakRow ? $peakRow->cnt : 0,
            'views_first_half'     => (clone $base)->whereBetween('view_count', [0, 45])->count(),
            'views_second_half'    => (clone $base)->whereBetween('view_count', [46, 90])->count(),
            'views_extra_time'     => (clone $base)->where('view_count', '>', 90)->count(),
        ];
    }

    private function viewerTimeline(MatchModel $match): array
    {
        return DB::table('match_views')
            ->where('match_id', $match->id)
            ->whereNotNull('view_count')
            ->selectRaw('view_count, COUNT(*) as views')
            ->groupBy('view_count')
            ->orderBy('view_count')
            ->get()
            ->map(fn ($r) => ['view_count' => $r->minute, 'views' => $r->viewers])
            ->values()
            ->toArray();
    }

    private function adMetrics(MatchModel $match): array
    {
        $events      = AdEvent::where('match_id', $match->id);
        $impressions = (clone $events)->where('event_type', 'injected')->count();
        $plays       = (clone $events)->where('event_type', 'played')->count();
        $completed   = (clone $events)->where('event_type', 'completed')->count();

        // play_time exists in schema; duration_played does NOT
        $totalPlayTime = (clone $events)->where('event_type', 'played')->sum('play_time');

        $byPeriod = (clone $events)
            ->whereNotNull('period')
            ->selectRaw('period, event_type, COUNT(*) as count')
            ->groupBy('period', 'event_type')
            ->get();

        $byPlatform = (clone $events)
            ->whereNotNull('platform')
            ->selectRaw('platform, COUNT(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform');

        return [
            'impressions'          => $impressions,
            'plays'                => $plays,
            'completed'            => $completed,
            'play_rate_pct'        => $impressions > 0 ? round($plays / $impressions * 100, 1)  : 0,
            'completion_rate_pct'  => $plays > 0       ? round($completed / $plays * 100, 1)    : 0,
            'total_play_time_secs' => (int) ($totalPlayTime ?? 0),
            'by_period'            => $byPeriod,
            'by_platform'          => $byPlatform,
        ];
    }

    /**
     * Ad impressions per match minute.
     * match_minute does NOT exist — use the match_views.minute as a proxy:
     * overlay ad injections bucketed by the minute at which the event was created
     * relative to match_date.
     */
    private function adTimeline(MatchModel $match): array
    {
        if (!$match->match_date) {
            return [];
        }

        // Bucket each ad injection into match minutes via created_at - match_date
        return AdEvent::where('match_id', $match->id)
            ->where('event_type', 'injected')
            ->whereNotNull('created_at')
            ->get(['created_at'])
            ->map(function ($e) use ($match) {
                $minute = (int) max(0, $match->match_date->diffInMinutes($e->created_at));
                return min($minute, 120); // cap at 120'
            })
            ->groupBy(fn ($m) => $m)
            ->map(fn ($group) => $group->count())
            ->map(fn ($count, $minute) => ['minute' => (int) $minute, 'impressions' => $count])
            ->values()
            ->sortBy('minute')
            ->values()
            ->toArray();
    }

    private function engagementBreakdown(MatchModel $match): array
    {
        $segments = [
            '0-15'  => [0,  15],
            '16-30' => [16, 30],
            '31-45' => [31, 45],
            '46-60' => [46, 60],
            '61-75' => [61, 75],
            '76-90' => [76, 90],
            '90+'   => [91, 999],
        ];

        return collect($segments)->map(fn ($range, $label) => [
            'segment' => $label,
            'views' => DB::table('match_views')
                ->where('match_id', $match->id)
                ->whereBetween('view_count', $range)
                ->count(),
        ])->values()->toArray();
    }

    private function peakMinutes(MatchModel $match): array
    {
        return DB::table('match_views')
            ->where('match_id', $match->id)
            ->whereNotNull('view_count')
            ->selectRaw('view_count, COUNT(*) as views')
            ->groupBy('view_count')
            ->orderByDesc('views')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['view_count' => $r->minute, 'views' => $r->viewers])
            ->values()
            ->toArray();
    }

    private function deviceSplit(MatchModel $match): array
    {
        $agents = DB::table('logs')
            ->where('reference_id', $match->id)
            ->whereNotNull('user_agent')
            ->pluck('user_agent');

        $devices  = ['mobile' => 0, 'tablet' => 0, 'desktop' => 0, 'bot' => 0];
        $browsers = [];

        foreach ($agents as $ua) {
            $ua = (string) $ua;
            if (preg_match('/bot|crawl|spider/i', $ua)) {
                $devices['bot']++;
            } elseif (preg_match('/tablet|ipad/i', $ua)) {
                $devices['tablet']++;
            } elseif (preg_match('/mobile|iphone|android(?!.*tablet)/i', $ua)) {
                $devices['mobile']++;
            } else {
                $devices['desktop']++;
            }

            $b = 'Other';
            if (preg_match('/Edg\//i', $ua))         $b = 'Edge';
            elseif (preg_match('/OPR\//i', $ua))     $b = 'Opera';
            elseif (preg_match('/Chrome\//i', $ua))  $b = 'Chrome';
            elseif (preg_match('/Firefox\//i', $ua)) $b = 'Firefox';
            elseif (preg_match('/Safari\//i', $ua))  $b = 'Safari';
            $browsers[$b] = ($browsers[$b] ?? 0) + 1;
        }

        arsort($browsers);

        return ['devices' => $devices, 'browsers' => $browsers];
    }

    public function matchRevenue(){
        
    }
}