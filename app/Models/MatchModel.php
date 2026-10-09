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

    public function bids()
    {
        return $this->hasMany(MatchBid::class, 'match_id');
    }

    /**
     * Memoized per-club scorer counts for this match instance — computed
     * once per request even though both score accessors below need it,
     * rather than running the grouped count query twice.
     */
    protected ?array $scorerCountsCache = null;

    protected function scorerCounts(): array
    {
        if ($this->scorerCountsCache !== null) {
            return $this->scorerCountsCache;
        }

        $byClub = $this->scorers()
            ->selectRaw('club_id, count(*) as c')
            ->groupBy('club_id')
            ->pluck('c', 'club_id');

        return $this->scorerCountsCache = [
            'home'    => (int) ($byClub[$this->home_club_id] ?? 0),
            'away'    => (int) ($byClub[$this->away_club_id] ?? 0),
            'has_any' => $byClub->isNotEmpty(),
        ];
    }

    /**
     * `home_score`/`away_score` are derived from the `scorers` table, not
     * trusted as independent stored values — see GoalService::record()/
     * remove(), which used to write client-supplied numbers here directly
     * (the Rust media server forwarding whatever the Android app's own
     * local counter said, which could drift — see confirmGoal/undoLastGoal
     * in services.dart). Once this match has *any* scorer row, these
     * accessors always return the live count, so the score can't disagree
     * with the goals actually on record, no matter what ends up in the
     * `home_score`/`away_score` columns.
     *
     * The stored column is still the source of truth for a match with zero
     * scorer rows — e.g. a historical result imported via
     * `BatchStoreMatchRequest`/`AdminMatchService` with only a final
     * scoreline and no per-goal breakdown. There's no way to "count" a
     * goal history that was never recorded goal-by-goal, so those matches
     * keep reading whatever was stored directly.
     */
    public function getHomeScoreAttribute($value)
    {
        $counts = $this->scorerCounts();
        return $counts['has_any'] ? $counts['home'] : (int) $value;
    }

    public function getAwayScoreAttribute($value)
    {
        $counts = $this->scorerCounts();
        return $counts['has_any'] ? $counts['away'] : (int) $value;
    }
}
