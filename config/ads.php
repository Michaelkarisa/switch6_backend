<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ad pricing (general campaigns + bid campaigns)
    |--------------------------------------------------------------------------
    | Images get a flat default duration. Video is priced per second beyond
    | the default duration.
    */
    'default_image_seconds' => env('AD_DEFAULT_IMAGE_SECONDS', 10),
    'price_per_second' => env('AD_PRICE_PER_SECOND', 10), // KES, per second after the 10th
    'general_campaign_base_price' => env('AD_GENERAL_BASE_PRICE', 1000), // KES, first 10s of a general campaign

    /*
    |--------------------------------------------------------------------------
    | Bid-tab base prices
    |--------------------------------------------------------------------------
    | During a live match, ads are image-only. Base starting price differs
    | by period. Advertisers must bid at or above the base for that period.
    | 5 slots exist per period per match; the 5 highest paid bids win.
    */
    'bid_base_prices' => [
        'before_match' => env('AD_BID_BASE_BEFORE_MATCH', 5000),
        'halftime'     => env('AD_BID_BASE_HALFTIME', 7500),
        'fulltime'     => env('AD_BID_BASE_FULLTIME', 6000),
    ],

    'slots_per_period' => 5,

    /*
    |--------------------------------------------------------------------------
    | Revenue share
    |--------------------------------------------------------------------------
    | Percentage of ad revenue for a match's bid slots that goes to the
    | broadcaster who owns that match, once the platform is actually
    | making money from advertising on it.
    */
    'broadcaster_revenue_share_percent' => (float) env('AD_BROADCASTER_REVENUE_SHARE_PERCENT', 50),

    // Whether broadcaster revenue-sharing is currently switched on. Per
    // spec this should only be enabled once the platform is actually
    // profitable from advertising overall — that's a finance-level call an
    // admin makes, not something computed automatically here.
    'revenue_sharing_enabled' => (bool) env('AD_REVENUE_SHARING_ENABLED', true),

];
