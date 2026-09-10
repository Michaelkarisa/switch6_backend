<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamEvent extends Model
{
    use HasFactory;

    /**
     * No created_at/updated_at columns on this table — only occurred_at.
     */
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'type',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'occurred_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(StreamSession::class, 'session_id');
    }
}
