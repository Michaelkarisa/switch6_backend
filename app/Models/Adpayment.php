<?php

namespace App\Models;

class AdPayment extends BaseUuidModel
{
    protected $table = 'ad_payments';

    protected $fillable = [
        'advertisement_id',
        'user_id',
        'amount_kes',
        'currency',
        'payment_method',
        'mpesa_reference',
        'transaction_code',
        'status',
        'paid_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount_kes' => 'integer',
        'paid_at'    => 'datetime',
        'metadata'   => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->hasOne(UserPlanSubscription::class, 'payment_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function markPaid(string $transactionCode): void
    {
        $this->update([
            'status'           => 'completed',
            'transaction_code' => $transactionCode,
            'paid_at'          => now(),
        ]);
    }
}