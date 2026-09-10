<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BroadcasterRank extends BaseAppendOnlyModel
{
    use HasFactory;

    /**
     * Only has an updated_at column — no created_at.
     */
    //const CREATED_AT = null;

    protected $fillable = [
        'broadcaster_id',
        'points',
    ];

    protected $casts = [
        'points' => 'float',
    ];
}
