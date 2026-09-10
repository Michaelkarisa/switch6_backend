<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $table = 'idempotency_keys';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'response',
        'status_code',
    ];

    protected $casts = [
        'response' => 'string',
        'status_code' => 'integer',
    ];
}