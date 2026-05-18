<?php

namespace App\Models;

use App\Models\Event;

class Advertisement extends BaseUuidModel
{
    protected $fillable = [
        'file_type',
        'file_path',
        'title',
        'duration',
        'period',
        'status',
        'end_date',
        'target_tags',
    ];

    protected $casts = [
        'target_tags' => 'array',
        'end_date' => 'date',
    ];

    public function events()
    {
        return $this->hasMany(AdEvent::class);
    }
}