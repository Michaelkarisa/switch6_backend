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
        'streamkeys',
        'home_formation',
        'away_formation',
        'cancellation_reason',
        'type',
        'slug',
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'home_score' => 'integer',
        'away_score' => 'integer',
        'streamkeys' => 'array',
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

    public function referee()
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

    public function views()
    {
        return $this->hasMany(MatchViews::class, 'match_id');
    }

    public function matchViews():int{
        $totalViews = $this->views()->sum('view_count');
        return $totalViews;
    }
    public function adEvents()
    {
        return $this->hasMany(AdEvent::class);
    }
}
