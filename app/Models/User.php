<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    use HasApiTokens, HasUuids, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'camera',
        'password',
        'isverified',
        'verification_date',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'camera'            => 'integer',
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

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function matchViews()
    {
        return $this->hasMany(MatchView::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(UserPlanSubscription::class);
    }

    public function adPayments()
    {
        return $this->hasMany(AdPayment::class);
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
        return $this->activeSubscription?->plan;
    }

    public function hasActivePlan(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function planAllows(string $feature): bool
    {
        $plan = $this->currentPlan();
        if (! $plan) {
            return false;
        }

        return (bool) ($plan->features[$feature] ?? $plan->{$feature} ?? false);
    }
}