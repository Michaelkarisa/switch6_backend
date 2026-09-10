<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    use HasApiTokens, HasUuids, Notifiable, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'isverified',
        'verification_date',
        'role',
        'status',
        'rank',
        'game_type',
        'country',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'isverified'        => 'boolean',
        'verification_date' => 'datetime',
        'role'              => 'string',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function logins()
    {
        return $this->hasMany(Login::class);
    }

    public function matches()
    {
        return $this->hasMany(MatchModel::class, 'author_id');
    }

    public function matchViews()
    {
        return $this->hasMany(MatchViews::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(UserPlanSubscription::class);
    }

    public function payments()
   {
       return $this->hasMany(Payment::class,'user_id');
    }

    // ── Plan helpers ──────────────────────────────────────────────────────

    /**
     * The user's current active subscription (most recent).
     */
    public function activeSubscription()
    {
        return $this->hasOne(UserPlanSubscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latestOfMany('starts_at');
    }

    public function currentPlan(): ?Plan
    {
        $isElligble = $this->elligible();
        if($isElligble){
            $plan = Plan::firstWhere('slug','pro');
            return $plan;
        }
        return $this->activeSubscription?->plan;
    }
    public function cameras():?int{
         $hasPlan = $this->hasActivePlan();
        if ($hasPlan) {
            $plan = $this->currentPlan();
           return $plan?->max_cameras;
        }
        return 0; 
    }

     public function quality():?int{
         $hasPlan = $this->hasActivePlan();
        if ($hasPlan) {
            $isElligble = $this->elligible();
        if($isElligble){
           return 720;
           }else{
            return $this->activeSubscription?->quality;
           }
        }
        return 480; 
    }
    /**
     * Whether the user is still within the 60-day free Pro trial window
     * from account creation. Uses diffInDays rather than dayOfYear so it
     * doesn't break across year boundaries.
     */
    public function elligible(): bool
    {
        if (! $this->created_at) {
            return false;
        }

        return $this->created_at->diffInDays(Carbon::now()) <= 60;
    }

    public function hasActivePlan(): bool
    {
         $isElligble = $this->elligible();
        if($isElligble){
            return true;
        }
        return $this->activeSubscription()->exists();
    }

    public function planAllows(string $feature): bool
    {
        $hasPlan = $this->hasActivePlan();
        if (!$hasPlan) {
            return false;
        }
        $plan = $this->currentPlan();

        return (bool) ($plan->features[$feature] ?? $plan->{$feature} ?? false);
    }


    public function ads(){
        return $this->hasMany(Advertisement::class,"user_id");
    }
}
