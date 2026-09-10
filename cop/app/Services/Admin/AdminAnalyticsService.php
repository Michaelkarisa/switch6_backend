<?php

namespace App\Services\Admin;

use App\Models\AdEvent;
use App\Models\Advertisement;
use App\Models\AuditLog;
use App\Models\MatchModel;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlanSubscription;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsService
{
    // ──────────────────────────────────────────────────────────────
    // Dashboard
    // ──────────────────────────────────────────────────────────────

    public function dashboard(): array
    {
            return [
                'users'         => $this->userStats(),
                'matches'       => $this->matchStats(),
                'subscriptions' => $this->subscriptionStats(),
                'revenue'       => $this->revenueStats(),
                'system'        => $this->systemStats(),
            ];
    }

    // ──────────────────────────────────────────────────────────────
    // Paginated audit logs
    // ──────────────────────────────────────────────────────────────

    public function auditLogs(\Illuminate\Http\Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return AuditLog::with('user:id,name,email')
            ->when($request->query('user_id'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->query('module'),  fn ($q, $v) => $q->where('module', $v))
            ->when($request->query('action'),  fn ($q, $v) => $q->where('action', $v))
            ->when($request->query('from'),    fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->query('to'),      fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($request->query('ip'),      fn ($q, $v) => $q->where('ip_address', $v))
            ->latest()
            ->paginate((int) $request->query('per_page', 50));
    }

    // ──────────────────────────────────────────────────────────────
    // Revenue over time
    // ──────────────────────────────────────────────────────────────

    public function revenueOverTime(int $days = 30): \Illuminate\Support\Collection
    {
        return Payment::where('status', 'completed')
            ->where('paid_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    // ──────────────────────────────────────────────────────────────
    // User growth over time
    // ──────────────────────────────────────────────────────────────

    public function userGrowth(int $days = 30): \Illuminate\Support\Collection
    {
        return User::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as new_users')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    // ──────────────────────────────────────────────────────────────
    // Match analytics
    // viewer_count does not exist on matches; we count match_views rows instead.
    // HOUR() / DAYOFWEEK() replaced with SQLite strftime().
    // ──────────────────────────────────────────────────────────────

    public function matchAnalytics(int $days = 30): array
    {
            $since = now()->subDays($days);

            // Matches per day
            $perDay = MatchModel::where('created_at', '>=', $since)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // By status
            $byStatus = MatchModel::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->mapWithKeys(fn ($r) => [$r->status => $r->count]);

            // By league (top 10)
            $byLeague = MatchModel::join('leagues', 'matches.league_id', '=', 'leagues.id')
                ->selectRaw('leagues.name as league, COUNT(*) as count')
                ->groupBy('leagues.id', 'leagues.name')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            // Viewer stats — count of match_view rows (no viewer_count col on matches)
            $viewerStats = DB::table('match_views')
                ->selectRaw('COUNT(*) as total_viewers, COUNT(DISTINCT match_id) as matches_with_views')
                ->first();

            $avgViewersPerMatch = 0;
            $peakViewers        = 0;
            if ($viewerStats && $viewerStats->matches_with_views > 0) {
                $avgViewersPerMatch = round($viewerStats->total_viewers / $viewerStats->matches_with_views);
                // Peak: most views recorded for a single match
                $peakRow = DB::table('match_views')
                    ->selectRaw('match_id, COUNT(*) as cnt')
                    ->groupBy('match_id')
                    ->orderByDesc('cnt')
                    ->first();
                $peakViewers = $peakRow ? $peakRow->cnt : 0;
            }

            // Peak match hours — SQLite: strftime('%H', datetime(match_date))
            $byHour = MatchModel::whereNotNull('match_date')
                ->selectRaw("HOUR(match_date) as hour, COUNT(*) as count")
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            // Top matches by view count (join match_views)
            $topMatches = DB::table('matches')
                ->leftJoin('match_views', 'matches.id', '=', 'match_views.match_id')
                ->leftJoin('clubs as hc', 'matches.home_club_id', '=', 'hc.id')
                ->leftJoin('clubs as ac', 'matches.away_club_id', '=', 'ac.id')
                ->selectRaw('matches.id, matches.status, matches.match_date,
                             hc.name as home_club, ac.name as away_club,
                             COUNT(match_views.id) as viewer_count')
                ->whereNull('matches.deleted_at')
                ->groupBy('matches.id', 'matches.status', 'matches.match_date', 'hc.name', 'ac.name')
                ->orderByDesc('viewer_count')
                ->limit(10)
                ->get();

            return [
                'per_day'       => $perDay,
                'by_status'     => $byStatus,
                'by_league'     => $byLeague,
                'viewer_stats'  => [
                    'total_viewers'  => $viewerStats->total_viewers ?? 0,
                    'avg_viewers'    => $avgViewersPerMatch,
                    'peak_viewers'   => $peakViewers,
                ],
                'by_hour'       => $byHour,
                'top_matches'   => $topMatches,
                'total_period'  => MatchModel::where('created_at', '>=', $since)->count(),
                'live_now'      => MatchModel::where('status', 'live')->count(),
            ];
    }

    // ──────────────────────────────────────────────────────────────
    // Advertisement performance analytics
    // HAVING on withCount replaced with a subquery approach for SQLite.
    // ──────────────────────────────────────────────────────────────

    public function adPerformance(int $days = 30): array
    {
            $since = now()->subDays($days);

            // Overall funnel
            $funnel = AdEvent::where('created_at', '>=', $since)
                ->selectRaw('event_type, COUNT(*) as count')
                ->groupBy('event_type')
                ->pluck('count', 'event_type');

            // By platform
            $byPlatform = AdEvent::where('created_at', '>=', $since)
                ->whereNotNull('platform')
                ->selectRaw('platform, event_type, COUNT(*) as count')
                ->groupBy('platform', 'event_type')
                ->get();

            // Impressions per day
            $perDay = AdEvent::where('created_at', '>=', $since)
                ->where('event_type', 'injected')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // By period slot
            $byPeriod = AdEvent::where('created_at', '>=', $since)
                ->whereNotNull('period')
                ->selectRaw('period, COUNT(*) as count')
                ->groupBy('period')
                ->get();

            // Top performing ads — use a subquery to avoid HAVING on non-aggregate (SQLite issue)
            $topAds = DB::table('advertisements')
                ->leftJoin('ad_events as ei', function ($join) {
                    $join->on('advertisements.id', '=', 'ei.advertisement_id')
                         ->where('ei.event_type', '=', 'injected');
                })
                ->leftJoin('ad_events as ep', function ($join) {
                    $join->on('advertisements.id', '=', 'ep.advertisement_id')
                         ->where('ep.event_type', '=', 'played');
                })
                ->leftJoin('ad_events as ec', function ($join) {
                    $join->on('advertisements.id', '=', 'ec.advertisement_id')
                         ->where('ec.event_type', '=', 'completed');
                })
                ->selectRaw('
                    advertisements.id,
                    advertisements.title,
                    advertisements.file_type,
                    advertisements.status,
                    COUNT(DISTINCT ei.id) as impressions_count,
                    COUNT(DISTINCT ep.id) as plays_count,
                    COUNT(DISTINCT ec.id) as completions_count
                ')
                ->whereNull('advertisements.deleted_at')
                ->groupBy('advertisements.id', 'advertisements.title', 'advertisements.file_type', 'advertisements.status')
                ->orderByDesc('completions_count')
                ->limit(10)
                ->get();

            // Avg / total play time
            $avgPlayTime = AdEvent::where('event_type', 'played')
                ->where('created_at', '>=', $since)
                ->avg('play_time');

            $totalViewerMinutes = AdEvent::where('event_type', 'played')
                ->where('created_at', '>=', $since)
                ->sum('play_time');

            return [
                'funnel'               => $funnel,
                'by_platform'          => $byPlatform,
                'impressions_per_day'  => $perDay,
                'by_period'            => $byPeriod,
                'top_ads'              => $topAds,
                'avg_play_time_secs'   => round($avgPlayTime ?? 0, 1),
                'total_viewer_minutes' => round(($totalViewerMinutes ?? 0) / 60),
            ];
    }

    // ──────────────────────────────────────────────────────────────
    // Device & user-agent analytics
    // HOUR() → strftime('%H', ...) for SQLite
    // ──────────────────────────────────────────────────────────────

    public function deviceAnalytics(int $days = 30): array
    {
            $since = now()->subDays($days);

            $agents = AuditLog::where('created_at', '>=', $since)
                ->whereNotNull('user_agent')
                ->pluck('user_agent');

            $devices  = ['mobile' => 0, 'tablet' => 0, 'desktop' => 0, 'bot' => 0];
            $browsers = [];
            $oses     = [];

            foreach ($agents as $ua) {
                $ua = (string) $ua;

                if (preg_match('/bot|crawl|spider|slurp|bingbot|googlebot/i', $ua)) {
                    $devices['bot']++;
                } elseif (preg_match('/tablet|ipad/i', $ua)) {
                    $devices['tablet']++;
                } elseif (preg_match('/mobile|android(?!.*tablet)|iphone|ipod|blackberry|opera mini|windows phone/i', $ua)) {
                    $devices['mobile']++;
                } else {
                    $devices['desktop']++;
                }

                $browser = 'Other';
                if (preg_match('/Edg\//i', $ua))             $browser = 'Edge';
                elseif (preg_match('/OPR\/|Opera/i', $ua))   $browser = 'Opera';
                elseif (preg_match('/Chrome\//i', $ua))      $browser = 'Chrome';
                elseif (preg_match('/Firefox\//i', $ua))     $browser = 'Firefox';
                elseif (preg_match('/Safari\//i', $ua))      $browser = 'Safari';
                elseif (preg_match('/MSIE|Trident/i', $ua))  $browser = 'IE';
                $browsers[$browser] = ($browsers[$browser] ?? 0) + 1;

                $os = 'Other';
                if (preg_match('/Windows NT 10/i', $ua))     $os = 'Windows 10/11';
                elseif (preg_match('/Windows/i', $ua))       $os = 'Windows';
                elseif (preg_match('/iPhone|iPad/i', $ua))   $os = 'iOS';
                elseif (preg_match('/Android/i', $ua))       $os = 'Android';
                elseif (preg_match('/Mac OS X/i', $ua))      $os = 'macOS';
                elseif (preg_match('/Linux/i', $ua))         $os = 'Linux';
                $oses[$os] = ($oses[$os] ?? 0) + 1;
            }

            arsort($browsers);
            arsort($oses);

            $topIps = AuditLog::where('created_at', '>=', $since)
                ->whereNotNull('ip_address')
                ->selectRaw('ip_address, COUNT(*) as requests')
                ->groupBy('ip_address')
                ->orderByDesc('requests')
                ->limit(10)
                ->get();

            // SQLite: strftime('%H', created_at) returns zero-padded '00'–'23'
            $byHourRaw = AuditLog::where('created_at', '>=', $since)
                ->selectRaw("HOUR(created_at) as hour, COUNT(*) as count")
                ->groupBy('hour')
                ->orderBy('hour')
                ->pluck('count', 'hour')
                ->toArray();

            $hourlyHeatmap = [];
            for ($h = 0; $h < 24; $h++) {
                $hourlyHeatmap[] = ['hour' => $h, 'count' => $byHourRaw[$h] ?? 0];
            }

            // Error detection — metadata is JSON stored as text in SQLite
            // Use LIKE instead of JSON_EXTRACT for broader compatibility
            $errorsByDay = AuditLog::where('created_at', '>=', $since)
                ->where(function ($q) {
                    $q->where('metadata', 'like', '%"status_code":4%')
                      ->orWhere('metadata', 'like', '%"status_code":5%');
                })
                ->selectRaw('DATE(created_at) as date, COUNT(*) as errors')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $uniqueUsersByDay = AuditLog::where('created_at', '>=', $since)
                ->whereNotNull('user_id')
                ->selectRaw('DATE(created_at) as date, COUNT(DISTINCT user_id) as unique_users')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'devices'             => $devices,
                'browsers'            => $browsers,
                'oses'                => $oses,
                'top_ips'             => $topIps,
                'hourly_heatmap'      => $hourlyHeatmap,
                'errors_by_day'       => $errorsByDay,
                'unique_users_by_day' => $uniqueUsersByDay,
                'total_requests'      => array_sum($devices),
            ];
    }

    // ──────────────────────────────────────────────────────────────
    // Log analytics
    // HOUR() / DAYOFWEEK() / JSON_EXTRACT → SQLite equivalents
    // ──────────────────────────────────────────────────────────────

    public function logAnalytics(int $days = 30): array
    {
        $since = now()->subDays($days);

        return [
            'by_module'              => $this->logsByModule($since),
            'top_actions'            => $this->topLogActions($since),
            'status_codes'           => $this->logStatusCodes($since),
            'avg_response_by_module' => $this->avgResponseByModule($since),
            'slow_requests'          => $this->slowRequests($since),
            'by_dow'                 => $this->logsByDow($since),
            'per_day'                => $this->logsPerDay($since),
        ];
    }

    private function logsByModule(\Carbon\Carbon $since): \Illuminate\Support\Collection
    {
        return AuditLog::where('created_at', '>=', $since)
            ->whereNotNull('module')
            ->selectRaw('module, COUNT(*) as count')
            ->groupBy('module')
            ->orderByDesc('count')
            ->limit(15)
            ->get();
    }

    private function topLogActions(\Carbon\Carbon $since): \Illuminate\Support\Collection
    {
        return AuditLog::where('created_at', '>=', $since)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
    }

    private function logStatusCodes(\Carbon\Carbon $since): array
    {
        // metadata is cast to array by AuditLog model — no json_decode needed on model instances.
        // pluck() returns raw DB values (strings) before casting, so we decode here.
        $metaRows    = AuditLog::where('created_at', '>=', $since)
            ->whereNotNull('metadata')
            ->pluck('metadata');

        $statusCodes = [];
        foreach ($metaRows as $meta) {
            $decoded     = is_array($meta) ? $meta : (json_decode((string) $meta, true) ?? []);
            $code        = (string) ($decoded['status_code'] ?? 'unknown');
            $statusCodes[$code] = ($statusCodes[$code] ?? 0) + 1;
        }
        arsort($statusCodes);

        return array_slice($statusCodes, 0, 8, true);
    }

    private function avgResponseByModule(\Carbon\Carbon $since): \Illuminate\Support\Collection
    {
        $timings = [];

        AuditLog::where('created_at', '>=', $since)
            ->whereNotNull('module')
            ->get(['module', 'metadata'])
            ->each(function ($row) use (&$timings) {
                $ms  = ($row->metadata ?? [])['duration_ms'] ?? null;
                if ($ms === null) return;
                $mod = $row->module;
                if (!isset($timings[$mod])) {
                    $timings[$mod] = ['sum' => 0, 'max' => 0, 'count' => 0];
                }
                $timings[$mod]['sum']   += $ms;
                $timings[$mod]['max']    = max($timings[$mod]['max'], $ms);
                $timings[$mod]['count'] += 1;
            });

        return collect($timings)
            ->map(fn ($v, $k) => [
                'module' => $k,
                'avg_ms' => $v['count'] > 0 ? round($v['sum'] / $v['count'], 1) : 0,
                'max_ms' => $v['max'],
            ])
            ->sortByDesc('avg_ms')
            ->values()
            ->take(10);
    }

    private function slowRequests(\Carbon\Carbon $since): \Illuminate\Support\Collection
    {
        return AuditLog::where('created_at', '>=', $since)
            ->get(['id', 'action', 'module', 'description', 'ip_address', 'created_at', 'metadata', 'user_id'])
            ->filter(fn ($row) => (($row->metadata ?? [])['duration_ms'] ?? 0) > 500)
            ->sortByDesc(fn ($row) => ($row->metadata ?? [])['duration_ms'] ?? 0)
            ->take(20)
            ->values();
    }

    private function logsByDow(\Carbon\Carbon $since): array
    {
        $raw    = AuditLog::where('created_at', '>=', $since)
            ->selectRaw("DAYOFWEEK(created_at) - 1 as dow, COUNT(*) as count")
            ->groupBy('dow')
            ->pluck('count', 'dow')
            ->toArray();

        $labels = [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];
        $result = [];
        for ($d = 0; $d <= 6; $d++) {
            $result[] = ['day' => $labels[$d], 'count' => $raw[$d] ?? 0];
        }

        return $result;
    }

    private function logsPerDay(\Carbon\Carbon $since): \Illuminate\Support\Collection
    {
        $totals = AuditLog::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $errors = ['4xx' => [], '5xx' => []];
        AuditLog::where('created_at', '>=', $since)
            ->whereNotNull('metadata')
            ->get(['created_at', 'metadata'])
            ->each(function ($row) use (&$errors) {
                $code = (int) (($row->metadata ?? [])['status_code'] ?? 0);
                $date = $row->created_at->toDateString();
                if ($code >= 500)      $errors['5xx'][$date] = ($errors['5xx'][$date] ?? 0) + 1;
                elseif ($code >= 400)  $errors['4xx'][$date] = ($errors['4xx'][$date] ?? 0) + 1;
            });

        return collect($totals)->map(fn ($total, $date) => [
            'date'          => $date,
            'total'         => $total,
            'client_errors' => $errors['4xx'][$date] ?? 0,
            'server_errors' => $errors['5xx'][$date] ?? 0,
        ])->values();
    }



    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    private function userStats(): array
    {
        return [
            'total'      => User::count(),
            'active'     => User::where('status', 'active')->count(),
            'suspended'  => User::where('status', 'suspended')->count(),
            'new_today'  => User::whereDate('created_at', today())->count(),
            'new_7_days' => User::where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }

    private function matchStats(): array
    {
        return [
            'total'     => MatchModel::count(),
            'live'      => MatchModel::where('status', 'live')->count(),
            'scheduled' => MatchModel::where('status', 'scheduled')->count(),
            'finished'  => MatchModel::where('status', 'finished')->count(),
            'today'     => MatchModel::whereDate('match_date', today())->count(),
        ];
    }

    private function subscriptionStats(): array
    {
        return [
            'active_total'    => UserPlanSubscription::where('status', 'active')->where('expires_at', '>', now())->count(),
            'expiring_7_days' => UserPlanSubscription::where('status', 'active')
                ->whereBetween('expires_at', [now(), now()->addDays(7)])->count(),
            'by_plan'         => Plan::withCount(['subscriptions as active_count' => fn ($q) =>
                    $q->where('status', 'active')->where('expires_at', '>', now())
                ])->get()->map(fn ($p) => ['plan' => $p->name, 'active' => $p->active_count]),
        ];
    }

    private function revenueStats(): array
    {
        $base = Payment::where('status', 'completed');
        return [
            'total'    => (clone $base)->sum('amount'),
            'today'    => (clone $base)->whereDate('paid_at', today())->sum('amount'),
            'month'    => (clone $base)->whereMonth('paid_at', now()->month)->sum('amount'),
            'transactions' => (clone $base)->count(),
            'currency' => 'KES'
        ];
    }

    private function systemStats(): array
    {
        return [
            'audit_logs_today' => AuditLog::whereDate('created_at', today())->count(),
            'failed_jobs'      => DB::table('failed_jobs')->count(),
            'pending_jobs'     => DB::table('jobs')->count(),
            'cache_driver'     => config('cache.default'),
            'queue_driver'     => config('queue.default'),
        ];
    }
}