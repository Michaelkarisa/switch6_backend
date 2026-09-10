<?php

namespace App\Models;

class Plan extends BaseUuidModel
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'duration_days',
        'max_matches',
        'max_cameras',
        'most_popular',
        'ads_enabled',
        'analytics_enabled',
        'is_active',
        'currency',
        'features',
        'quality',
    ];

    protected $casts = [
        'price'              => 'integer',
        'duration_days'      => 'integer',
        'max_matches'        => 'integer',
        'max_cameras'        => 'integer',
        'quality'            => 'array',
        'ads_enabled'        => 'boolean',
        'analytics_enabled'  => 'boolean',
        'is_active'          => 'boolean',
        'most_popular'       => 'boolean',
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

    /**
     * Whether this plan has the highest active-subscription count of all
     * active plans. If nobody has subscribed to anything yet, the plan
     * with slug "standard" is treated as the default most-popular plan.
     */
    public function mostpopular(): bool
    {
        $counts = UserPlanSubscription::query()
            ->where('status', 'active')
            ->whereIn('plan_id', static::where('is_active', true)->pluck('id'))
            ->selectRaw('plan_id, count(*) as total')
            ->groupBy('plan_id')
            ->pluck('total', 'plan_id');

        if ($counts->isEmpty() || $counts->sum() === 0) {
            return $this->slug === 'standard';
        }

        $topPlanId = $counts->sortDesc()->keys()->first();

        return $topPlanId === $this->id;
    }
}