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
    // ── Scopes ────────────────────────────────────────────────────────────

    public function paymentStatus(){
        $transaction = $this->transactions()->latest();
        $this->status = $transaction->status;
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
