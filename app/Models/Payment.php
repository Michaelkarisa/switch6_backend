<?php

namespace App\Models;

class Payment extends BaseUuidModel
{
    protected $fillable = [
        'phone',
        'amount',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];
}
