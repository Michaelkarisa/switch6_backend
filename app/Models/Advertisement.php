<?php

namespace App\Models;

class Advertisement extends BaseUuidModel
{
    protected $fillable = [
        'user_id',
        'file_type',
        'file_path',
        'title',
        'duration',
        'period',
        'status',
        'end_date',
        'target_tags',
        'payment_id',
        'campaign_type',
        'broadcaster_id',
        'price',
    ];

    protected $casts = [
        'target_tags' => 'array',
        'end_date' => 'date',
    ];

    public function events()
    {
        return $this->hasMany(AdEvent::class);
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
    
   public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
    public function bids()
    {
        return $this->hasMany(MatchBid::class);
    }

    public function broadcaster()
    {
        return $this->belongsTo(User::class, 'broadcaster_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
}