<?php

namespace App\Models;

class Transaction extends BaseUuidModel
{
    protected $fillable = [
        'phone',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];
}
