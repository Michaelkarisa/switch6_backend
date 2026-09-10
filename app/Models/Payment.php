<?php

namespace App\Models;

class Payment extends BaseAppendOnlyModel
{
    protected $table = 'payments';

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'payment_method',
        'reference',
        'transaction_code',
        'status',
        'paid_at',
        'notes',
        'phone',
        'type',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at'    => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

   

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function transactions(){

    return $this->hasMany(Transaction::class);
    }

    public function matchBids()
    {
        return $this->hasMany(MatchBid::class, 'payment_id');
    }
    // ── Scopes ────────────────────────────────────────────────────────────

    /**
     * Recompute this payment's status from its latest transaction record
     * and persist it. Used both by the provider callback and by manual
     * polling so the payment always mirrors the most recent transaction.
     */
    public function paymentStatus(): string
    {
        $latest = $this->transactions()->latest('created_at')->first();

        if (! $latest || ! $latest->status) {
            return $this->status;
        }

        if ($latest->status !== $this->status) {
            $attributes = ['status' => $latest->status];

            if ($latest->status === 'completed') {
                $attributes['transaction_code'] = $latest->transaction_code ?? $this->transaction_code;
                $attributes['paid_at'] = $this->paid_at ?? now();
            }

            $this->update($attributes);
        }

        return $this->status;
    }
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
