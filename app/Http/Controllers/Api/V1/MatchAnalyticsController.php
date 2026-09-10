<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Services\ApiResponseService;
use App\Services\MatchAnalyticsService;
use Illuminate\Http\JsonResponse;

class MatchAnalyticsController extends Controller
{
    public function __construct(
        private MatchAnalyticsService $analytics,
        private ApiResponseService    $response,
    ) {}

    /**
     * GET /v1/matches/{match}/analytics
     * Returns full per-match analytics for the broadcaster dashboard.
     */
        public function show(string $id): JsonResponse
    {     $match = MatchModel::Where('id',$id)->orWhere('slug',$id)->first();
        // Access is scoped by the broadcaster/admin route middleware group.
        // Additional ownership check can be added here if a MatchPolicy is created.

        return $this->response->success(
            $this->analytics->forMatch($match),
            'Match analytics fetched successfully'
        );
    }
}