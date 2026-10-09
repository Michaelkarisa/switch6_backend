<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchViews extends BaseAppendOnlyModel
{
    use HasFactory;

    /**
     * `updated_at` doesn't exist on this table (rows are append-only
     * samples) — only `created_at`, set explicitly since $timestamps is off.
     */
    public $timestamps = false;

    protected $fillable = [
        'match_id',
        'platform',
        'view_count',
        'created_at',
    ];

    protected $casts = [
        'view_count'  => 'integer',
        'created_at'  => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }
}
