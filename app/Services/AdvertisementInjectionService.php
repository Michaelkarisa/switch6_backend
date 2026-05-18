<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\Event;

class AdvertisementInjectionService
{
    public function inject($ad, $match, $streamId)
    {
        app(AdEventService::class)->log([
            'advertisement_id' => $ad->id,
            'match_id' => $match,
            'event_type' => 'injected',
            'stream_id' => $streamId,
        ]);

        return [
            'file_type' => $ad->file_type,
            'file_path' => $ad->file_path,
            'duration' => $ad->duration,
        ];
    }
}