<?php

namespace App\Services;

use App\Models\MatchModel;
use App\Models\Scorer;

class GoalService
{
    public function __construct(private AuditLogService $audit) {}

    /** Scorers for a match, oldest first, with names resolved for display. */
    public function listForMatch(MatchModel $match): \Illuminate\Support\Collection
    {
        return Scorer::where('match_id', $match->id)
            ->with(['player:id,name', 'assistPlayer:id,name', 'club:id,name'])
            ->orderBy('minute')->orderBy('created_at')
            ->get()
            ->map(fn (Scorer $s) => [
                'id'          => $s->id,
                'club_id'     => $s->club_id,
                'club'        => $s->club?->name,
                'side'        => $s->club_id === $match->home_club_id ? 'home' : 'away',
                'player_id'   => $s->player_id,
                'player'      => $s->player?->name,
                'assist_player_id' => $s->assist_player_id,
                'assist'      => $s->assistPlayer?->name,
                'minute'      => $s->minute,
                'goal_type'   => $s->goal_type,
            ]);
    }

    public function record(MatchModel $match, array $data): Scorer
    {
        if (! in_array($data['club_id'], [$match->home_club_id, $match->away_club_id], true)) {
            abort(response()->json(['success'=>false,'message'=>'club_id does not match home or away club','errors'=>null,'data'=>null], 400));
        }

        $scorer = Scorer::create([
            'match_id' => $match->id,
            'player_id' => $data['player_id'],
            'club_id' => $data['club_id'],
            'minute' => $data['minute'],
            'goal_type' => $data['goal_type'] ?? 'regular',
            'assist_player_id' => $data['assist_player_id'] ?? null,
        ]);

        // Derive the stored score from the scorers table itself — not from
        // client-supplied home_score/away_score (previously
        // $data['home_score']/$data['away_score'], forwarded from whatever
        // the Android app's own local counter said; see confirmGoal in
        // services.dart — that counter isn't assumed authoritative). Mirrors
        // remove()'s recalculation below, and keeps these columns correct
        // as the fallback MatchModel::getHomeScoreAttribute/
        // getAwayScoreAttribute use for matches with zero scorer rows.
        $match->home_score = Scorer::where('match_id', $match->id)
            ->where('club_id', $match->home_club_id)->count();
        $match->away_score = Scorer::where('match_id', $match->id)
            ->where('club_id', $match->away_club_id)->count();
        $match->save();

        $this->audit->log('goal_recorded', 'goals', 'Goal recorded', [
            'match_id' => $match->id,
            'scorer_id' => $scorer->id,
            'home_score' => $match->home_score,
            'away_score' => $match->away_score,
        ], $scorer);

        return $scorer;
    }

    /**
     * Removes the most recently recorded goal for a match, without the
     * caller needing to know its scorer_id — this is what backs
     * "undo my last goal" from the Rust media server's "undoGoal" control
     * action, which only ever knows the match, not which Scorer row its
     * corresponding "goal" produced (add_goal() is fire-and-forget and
     * never captures the response). Reuses remove()'s score-recalculation
     * so home_score/away_score stay derived from the remaining Scorer rows
     * rather than trusting a client-sent decrement.
     *
     * Returns the removed Scorer (or null if this match has no goals to
     * undo — e.g. a duplicate/late-arriving "undoGoal" after the first one
     * already removed the only scorer).
     */
    public function removeLatestForMatch(MatchModel $match, ?\Illuminate\Http\Request $request = null): ?Scorer
    {
        $scorer = Scorer::where('match_id', $match->id)->latest('created_at')->first();
        if ($scorer) {
            $this->remove($scorer, $request);
        }
        return $scorer;
    }

    public function remove(Scorer $scorer, ?\Illuminate\Http\Request $request = null): void
    {
        $match = MatchModel::find($scorer->match_id);

        $scorerId = $scorer->id;
        $scorer->delete();

        // Recalculate score from remaining scorers
        if ($match) {
            $match->home_score = Scorer::where('match_id', $match->id)
                ->where('club_id', $match->home_club_id)->count();
            $match->away_score = Scorer::where('match_id', $match->id)
                ->where('club_id', $match->away_club_id)->count();
            $match->save();
        }

        $this->audit->log('goal_removed', 'goals', 'Goal removed', [
            'scorer_id' => $scorerId,
            'match_id'  => $match?->id,
        ], request: $request);
    }
}
