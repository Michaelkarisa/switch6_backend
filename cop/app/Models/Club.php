<?php

namespace App\Models;

class Club extends BaseUuidModel
{
    protected $fillable = [
        'name',
        'city',
        'founded_year',
        'stadium',
        'jersey_color',
        'logo_url',
    ];

    protected $casts = [
        'founded_year' => 'integer',
        'jersey_color' => 'integer',
    ];
    protected $hidden = [
        'author_id',
    ];

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function homeMatches()
    {
        return $this->hasMany(MatchModel::class, 'home_club_id');
    }

    public function awayMatches()
    {
        return $this->hasMany(MatchModel::class, 'away_club_id');
    }

    public function lineups()
    {
        return $this->hasMany(Lineup::class);
    }

    public function scorers()
    {
        return $this->hasMany(Scorer::class);
    }

    public function managers(){

    return $this->hasMany(ClubManager::class);
    }
}
