<?php

namespace App\Models;

class Plan extends BaseUuidModel
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_kes',
        'duration_days',
        'max_matches',
        'max_streams',
        'ads_enabled',
        'analytics_enabled',
        'is_active',
        'features',
    ];

    protected $casts = [
        'price_kes'          => 'integer',
        'duration_days'      => 'integer',
        'max_matches'        => 'integer',
        'max_streams'        => 'integer',
        'ads_enabled'        => 'boolean',
        'analytics_enabled'  => 'boolean',
        'is_active'          => 'boolean',
        'features'           => 'array',
    ];

    public function subscriptions()
    {
        return $this->hasMany(UserPlanSubscription::class);
    }

    public function activeSubscriptions()
    {
        return $this->hasMany(UserPlanSubscription::class)->where('status', 'active');
    }
}