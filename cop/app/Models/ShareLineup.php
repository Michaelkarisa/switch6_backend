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
    ];

    public function sender(){
        return $this->BelongsTo(User::class,'sender_id');
    }

    public function match(){
        return $this->hasOne(MatchModel::class,'match_id');
    }

    public function recepient(){
        return $this->hasOne(User::class, 'recepient_id');
    }

    public function club(){
        return $this->hasOne(Club::class, 'club_id');
    }
}
