<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAnalyticsService;
use App\Services\ApiResponseService as Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAnalyticsController extends Controller
{
    public function __construct(private AdminAnalyticsService $analytics) {}

    public function dashboard(): JsonResponse
    {
        return Api::success($this->analytics->dashboard(), 'Dashboard fetched successfully');
    }

    public function auditLogs(Request $request): JsonResponse
    {
        return Api::paginated($this->analytics->auditLogs($request), 'Audit logs fetched successfully');
    }

    public function revenue(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        return Api::success($this->analytics->revenueOverTime($days), 'Revenue fetched', ['days' => $days]);
    }

    public function userGrowth(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        return Api::success($this->analytics->userGrowth($days), 'User growth fetched', ['days' => $days]);
    }
}
