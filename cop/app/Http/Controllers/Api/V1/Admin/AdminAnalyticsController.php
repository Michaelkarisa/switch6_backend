<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAnalyticsService;
use App\Services\ApiResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAnalyticsController extends Controller
{
    public function __construct(
        private AdminAnalyticsService $analytics,
        private ApiResponseService    $response,
    ) {}

    /** GET /admin/dashboard */
    public function dashboard(): JsonResponse
    {
        return $this->response->success($this->analytics->dashboard());
    }

    /** GET /admin/analytics/audit-logs */
    public function auditLogs(Request $request): JsonResponse
    {
        $paginated = $this->analytics->auditLogs($request);
        return $this->response->paginated($paginated);
    }

    /** GET /admin/analytics/revenue?days=30 */
    public function revenue(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        return $this->response->success($this->analytics->revenueOverTime($days));
    }

    /** GET /admin/analytics/user-growth?days=30 */
    public function userGrowth(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        return $this->response->success($this->analytics->userGrowth($days));
    }

    /** GET /admin/analytics/matches?days=30  — NEW */
    public function matchAnalytics(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        return $this->response->success($this->analytics->matchAnalytics($days));
    }

    /** GET /admin/analytics/ads?days=30  — NEW */
    public function adPerformance(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        return $this->response->success($this->analytics->adPerformance($days));
    }

    /** GET /admin/analytics/devices?days=30  — NEW */
    public function deviceAnalytics(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        return $this->response->success($this->analytics->deviceAnalytics($days));
    }

    /** GET /admin/analytics/logs?days=30  — NEW */
    public function logAnalytics(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        return $this->response->success($this->analytics->logAnalytics($days));
    }
}