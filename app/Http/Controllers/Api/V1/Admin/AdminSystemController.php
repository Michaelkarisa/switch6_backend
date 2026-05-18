<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAnalyticsService;
use App\Services\ApiResponseService as Api;
use App\Services\AuditLogService;
use App\Services\HealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminSystemController extends Controller
{
    public function __construct(
        private HealthService $health,
        private AuditLogService $audit,
    ) {}

    public function health(): JsonResponse
    {
        $cacheOk = false;
        try {
            $k = 'hping:' . time();
            Cache::put($k, true, 5);
            $cacheOk = Cache::get($k) === true;
            Cache::forget($k);
        } catch (\Throwable) {}

        return Api::success([
            'app'      => $this->health->health(),
            'database' => $this->health->databaseStatus(),
            'cache'    => ['status' => $cacheOk ? 'connected' : 'error', 'driver' => config('cache.default')],
            'queue'    => [
                'driver'       => config('queue.default'),
                'pending'      => DB::table('jobs')->count(),
                'failed'       => DB::table('failed_jobs')->count(),
                'after_commit' => config('queue.connections.' . config('queue.default') . '.after_commit'),
            ],
        ], 'System health fetched successfully');
    }

    public function clearCache(Request $request): JsonResponse
    {
        Cache::flush();
        $this->audit->log('admin_cache_cleared', 'system', 'Admin cleared cache', [], null, $request);
        return Api::success(null, 'Cache cleared successfully');
    }

    public function clearCacheKey(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:255']]);
        Cache::forget($data['key']);
        return Api::success(null, "Cache key '{$data['key']}' cleared");
    }

    public function failedJobs(Request $request): JsonResponse
    {
        $jobs = DB::table('failed_jobs')->orderByDesc('failed_at')
            ->paginate((int) $request->query('per_page', 20));

        return Api::paginated($jobs, 'Failed jobs fetched successfully');
    }

    public function retryJob(Request $request, int $id): JsonResponse
    {
        $job = DB::table('failed_jobs')->where('id', $id)->first();
        if (! $job) return Api::notFound("Failed job #{$id} not found");

        Artisan::call('queue:retry', ['id' => [$id]]);

        $this->audit->log('admin_job_retried', 'system', 'Admin retried failed job', ['job_id' => $id], null, $request);

        return Api::success(null, "Job #{$id} queued for retry");
    }

    public function deleteJob(Request $request, int $id): JsonResponse
    {
        Artisan::call('queue:forget', ['id' => $id]);
        $this->audit->log('admin_job_deleted', 'system', 'Admin deleted failed job', ['job_id' => $id], null, $request);
        return Api::success(null, "Job #{$id} deleted");
    }

    public function flushFailedJobs(Request $request): JsonResponse
    {
        $count = DB::table('failed_jobs')->count();
        Artisan::call('queue:flush');
        $this->audit->log('admin_jobs_flushed', 'system', 'Admin flushed failed jobs', ['count' => $count], null, $request);
        return Api::success(null, "{$count} failed jobs flushed");
    }
}
