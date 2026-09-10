<?php

namespace App\Models;

class Transaction extends BaseAppendOnlyModel
{
    protected $fillable = [
        'reference',
        'transaction_code',
        'status',
        'payment_id',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function payment(){

    return $this->belongsTo(Payment::class,'payment_id');
    }
}
