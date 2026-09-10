<?php

namespace App\Models;

class ClubManager extends BaseUuidModel
{

  protected $fillable = [
        'name',
        'phone',
        'nationality',
        'profile_url',
        'club_id',
        'role',
    ];

     protected $hidden = [
        'author_id',
    ];

    public function club(){

    return $this->belongsTo(Club::class,'club_id');
    }

}