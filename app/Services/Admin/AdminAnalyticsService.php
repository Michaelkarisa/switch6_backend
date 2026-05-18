<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\MatchModel;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlanSubscription;
use App\Models\AdPayment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsService
{
    private const CACHE_TTL = 300; // 5 minutes

    public function dashboard(): array
    {
        return Cache::remember('admin:dashboard', self::CACHE_TTL, function () {
            return [
                'users'         => $this->userStats(),
                'matches'       => $this->matchStats(),
                'subscriptions' => $this->subscriptionStats(),
                'revenue'       => $this->revenueStats(),
                'system'        => $this->systemStats(),
            ];
        });
    }

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
        $base = AdPayment::where('status', 'completed');
        return [
            'total_kes'    => (clone $base)->sum('amount_kes'),
            'today_kes'    => (clone $base)->whereDate('paid_at', today())->sum('amount_kes'),
            'month_kes'    => (clone $base)->whereMonth('paid_at', now()->month)->sum('amount_kes'),
            'transactions' => (clone $base)->count(),
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

    public function auditLogs(\Illuminate\Http\Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return AuditLog::with('user:id,name,email')
            ->when($request->query('user_id'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->query('module'),  fn ($q, $v) => $q->where('module', $v))
            ->when($request->query('action'),  fn ($q, $v) => $q->where('action', $v))
            ->when($request->query('from'),    fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->query('to'),      fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate((int) $request->query('per_page', 50));
    }

    public function revenueOverTime(int $days = 30): \Illuminate\Support\Collection
    {
        return AdPayment::where('status', 'completed')
            ->where('paid_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(paid_at) as date, SUM(amount_kes) as total_kes, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function userGrowth(int $days = 30): \Illuminate\Support\Collection
    {
        return User::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as new_users')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
