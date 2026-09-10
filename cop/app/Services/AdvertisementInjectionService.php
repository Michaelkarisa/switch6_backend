<?php

namespace App\Services;

use App\Models\Advertisement;

class AdvertisementInjectionService
{
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
        return Advertisement::where('status', 'active')
            ->where(function ($q) use ($period) {
                $q->where('period', $period)->orWhereNull('period');
            })
            ->get()
            ->sortByDesc(function ($ad) use ($matchId) {
                return $ad->events()
                    ->where('match_id', $matchId)
                    ->count();
            })
            ->first();
    }
}
