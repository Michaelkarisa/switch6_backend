<?php

namespace App\Models;

class Login extends BaseAppendOnlyModel
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
