<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AdEventService;
use App\Services\ApiResponseService as Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdEventController extends Controller
{
    /** POST /v1/ads/events */
    public function store(Request $request, AdEventService $service): JsonResponse
    {
        $data = $request->validate([
            'advertisement_id' => ['required'],
            'match_id'         => ['required'],
            'event_type'       => ['required', 'in:injected,played,completed'],
            'stream_id'        => ['nullable'],
            'match_minute'     => ['nullable'],
        ]);

        $event = $service->log($data);

        return Api::created($event, 'Ad event logged successfully');
    }
}
