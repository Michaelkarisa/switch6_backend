<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for ad pricing so it's never trusted from the
 * client. Images get a flat default duration; video is charged per second
 * beyond that default. Bid-tab prices additionally start from a
 * period-specific base the advertiser must meet or beat.
 */
class AdPricingService
{
    /** Extra seconds cost for video beyond the free default duration. */
    public function extraSecondsCost(string $fileType, int $duration): int
    {
        if ($fileType !== 'video') {
            return 0;
        }

        $defaultSeconds = (int) config('ads.default_image_seconds', 10);
        $extraSeconds = max(0, $duration - $defaultSeconds);

        return $extraSeconds * (int) config('ads.price_per_second', 10);
    }

    /** Price for a general-tab campaign: flat base + any video overage. */
    public function generalCampaignPrice(string $fileType, int $duration): int
    {
        return (int) config('ads.general_campaign_base_price', 1000)
            + $this->extraSecondsCost($fileType, $duration);
    }

    /** Minimum starting bid for a given bid-tab period. */
    public function basePriceForPeriod(string $period): int
    {
        $base = config("ads.bid_base_prices.{$period}");

        if ($base === null) {
            throw ValidationException::withMessages([
                'period' => ['Period must be one of: before_match, halftime, fulltime.'],
            ]);
        }

        return (int) $base;
    }

    /**
     * Bid-tab ads are image-only during live match periods, per spec.
     */
    public function assertBidFileTypeAllowed(string $fileType): void
    {
        if ($fileType !== 'image') {
            throw ValidationException::withMessages([
                'file_type' => ['Bid-tab ad slots only accept images.'],
            ]);
        }
    }
}
