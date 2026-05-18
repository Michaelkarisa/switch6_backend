<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ApiResponseService as Api;
use App\Services\HealthService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(private HealthService $healthService) {}

    /** GET /v1/health */
    public function health(): JsonResponse
    {
        $status = $this->healthService->health();

        return Api::success($status, 'Health check passed');
    }

    /** GET /v1/health/database */
    public function databaseStatus(): JsonResponse
    {
        $status = $this->healthService->databaseStatus();

        return Api::success($status, 'Database status fetched successfully');
    }
}
