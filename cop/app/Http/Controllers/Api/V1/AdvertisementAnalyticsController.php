<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AdvertisementAnalyticsService;
use App\Services\ApiResponseService as Api;
use Illuminate\Http\JsonResponse;

class AdvertisementAnalyticsController extends Controller
{
    /** GET /v1/ads/{adId}/analytics */
    public function show(string $adId, AdvertisementAnalyticsService $service): JsonResponse
    {
        $summary = $service->summary($adId);

        if (! $summary) {
            return Api::notFound('Analytics not found for this advertisement');
        }

        return Api::success($summary, 'Advertisement analytics fetched successfully');
    }
}
