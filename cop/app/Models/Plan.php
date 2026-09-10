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

    public function mostpopular(){
        //how many people have subscribed to this plan verses the other if has more counts compared to the others the true if not false.
         $plans = Plan::all();
         $active = $this->activeSubscriptions();
         $plans->each(function( $plan) {
         $allPlans =collect([]);
         $count = UserPlanSubscription::where('plan_id',$plan->id)->count();
         $allPlans->add([
            "plan_id"=>$plan->id,
            'count' => $count
         ]);
        });
        //compare this active vs the others active if no subscription choose the standard one as the defualt mostpopular.
        return false;
    }
}