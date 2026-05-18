<?php

namespace App\Models;

class MatchModel extends BaseUuidModel
{
    protected $table = 'matches';

    protected $fillable = [
        'league_id',
        'home_club_id',
        'away_club_id',
        'referee_id',
        'author_id',
        'match_date',
        'home_score',
        'away_score',
        'status',
        'venue',
        'highlights',
        'url',
        'home_formation',
        'away_formation',
        'cancellation_reason',
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'home_score' => 'integer',
        'away_score' => 'integer',
    ];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function homeClub()
    {
        return $this->belongsTo(Club::class, 'home_club_id');
    }

    public function awayClub()
    {
        return $this->belongsTo(Club::class, 'away_club_id');
    }

    public function refereeRecord()
    {
        return $this->belongsTo(Referee::class, 'referee_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function lineups()
    {
        return $this->hasMany(Lineup::class, 'match_id');
    }

    public function scorers()
    {
        return $this->hasMany(Scorer::class, 'match_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'match_id');
    }

    public function views()
    {
        return $this->hasMany(MatchView::class, 'match_id');
    }
}
