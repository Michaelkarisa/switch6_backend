<?php

namespace App\Services;

use App\Models\MatchModel;
use App\Models\Scorer;

class GoalService
{
    public function __construct(private AuditLogService $audit) {}

    public function record(MatchModel $match, array $data): Scorer
    {
        if (! in_array($data['club_id'], [$match->home_club_id, $match->away_club_id], true)) {
            abort(response()->json(['success'=>false,'message'=>'club_id does not match home or away club','errors'=>null,'data'=>null], 400));
        }

        $match->update([
            'home_score' => $data['home_score'],
            'away_score' => $data['away_score'],
        ]);

        $scorer = Scorer::create([
            'match_id' => $match->id,
            'player_id' => $data['player_id'],
            'club_id' => $data['club_id'],
            'minute' => $data['minute'],
            'goal_type' => $data['goal_type'] ?? 'regular',
            'assist_player_id' => $data['assist_player_id'] ?? null,
        ]);

        $this->audit->log('goal_recorded', 'goals', 'Goal recorded', [
            'match_id' => $match->id,
            'scorer_id' => $scorer->id,
            'home_score' => $match->home_score,
            'away_score' => $match->away_score,
        ], $scorer);

        return $scorer;
    }
}
