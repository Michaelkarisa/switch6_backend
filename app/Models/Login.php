<?php

namespace App\Models;

class Login extends BaseUuidModel
{
     protected $table = 'logins';
    protected $fillable = [
        'user_id',
        'login_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
