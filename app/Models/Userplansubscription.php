<?php

namespace App\Models;

use Carbon\Carbon;

class UserPlanSubscription extends BaseAppendOnlyModel
{
    protected $table = 'user_plan_subscriptions';

    protected $fillable = [
        'user_id',
        'plan_id',
        'payment_id',
        'status',
        'starts_at',
        'expires_at',
        'cancelled_at',
        'expiry_warning_sent_at',
        'quality',
    ];

    protected $casts = [
        'starts_at'              => 'datetime',
        'expires_at'             => 'datetime',
        'cancelled_at'           => 'datetime',
        'expiry_warning_sent_at' => 'datetime',
        'quality'                => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }

    public function daysUntilExpiry(): int
    {
        return (int) now()->diffInDays($this->expires_at, absolute: true);
    }

    public function needsExpiryWarning(): bool
    {
        return $this->isActive()
            && $this->daysUntilExpiry() <= 5
            && $this->expiry_warning_sent_at === null;
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('expires_at', '>', now());
    }

    public function scopeExpiringSoon($query, int $days = 5)
    {
        return $query->active()
            ->whereNull('expiry_warning_sent_at')
            ->where('expires_at', '<=', now()->addDays($days));
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')->where('expires_at', '<=', now());
    }
}
