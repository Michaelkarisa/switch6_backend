<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\AdEvent;

class AdvertisementAnalyticsService
{
    public function summary(string $adId)
    {
        $events = AdEvent::where('advertisement_id', $adId)->get();

        return [
            'impressions' => $events->where('event_type', 'injected')->count(),
            'plays'       => $events->where('event_type', 'played')->count(),
            'completed'   => $events->where('event_type', 'completed')->count(),
        ];
    }
}