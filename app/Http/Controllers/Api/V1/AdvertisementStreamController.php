<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Services\AdvertisementInjectionService;
use App\Services\ApiResponseService as Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvertisementStreamController extends Controller
{
    /** POST /v1/ads/inject */
    public function inject(Request $request, AdvertisementInjectionService $service): JsonResponse
    {
        $ad = Advertisement::findOrFail($request->ad_id);

        $payload = $service->inject($ad, $request->match_id, $request->stream_id);

        return Api::success($payload, 'Advertisement injected into stream successfully');
    }
}
