<?php

namespace App\Models;

class AuditLog extends BaseUuidModel
{

 protected $table = 'logs';
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'reference_id',
        'description',
        'ip_address',
        'user_agent',
        'metadata',
    ];
 
    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
