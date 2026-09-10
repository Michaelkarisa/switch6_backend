<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareLineup extends BaseAppendOnlyModel
{
    //

     protected $fillable = [
        'club_id',
        'match_id',
        'sender_id',
        'recepient_id',
        'status',
        'imported_at',
        'imported_into_match_id',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    public function sender(){
        return $this->BelongsTo(User::class,'sender_id');
    }

    public function match(){
        return $this->belongsTo(MatchModel::class,'match_id');
    }

    public function recepient(){
        return $this->belongsTo(User::class, 'recepient_id');
    }

    public function club(){
        return $this->belongsTo(Club::class, 'club_id');
    }
}
