<?php

namespace App\Services;

use App\Models\Advertisement;

/**
 * Handles the mechanics of pushing an ad into a live stream and logging
 * that it happened. Ad *selection* logic lives in AdTargetingService (this
 * used to duplicate that logic — now delegates to it) so there's a single
 * place that decides which ad wins a slot.
 */
class AdvertisementInjectionService
{
    public function __construct(private AdTargetingService $targeting) {}

    public function inject($ad, $match, $streamId)
    {
        app(AdEventService::class)->log([
            'advertisement_id' => $ad->id,
            'match_id'         => $match,
            'event_type'       => 'injected',
            'stream_id'        => $streamId,
        ]);

        return [
            'file_type' => $ad->file_type,
            'file_path' => $ad->file_path,
            'duration'  => $ad->duration,
        ];
    }

    /**
     * Pick the best active advertisement for a given match and period.
     * Used by AdvertisementStreamController::forMatch.
     */
    public function selectForMatch(?string $matchId, ?string $period): ?Advertisement
    {
        return $this->targeting->pickBestAd($matchId, $period);
    }
}
