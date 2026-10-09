<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalRequest;
use App\Models\MatchModel;
use App\Services\ApiResponseService as Api;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Scorer;
class GoalController extends Controller
{
    public function __construct(private GoalService $goals) {}

    /** GET /v1/matches/{match}/goals — scorers plus the score derived from them. */
    public function index(MatchModel $match): JsonResponse
    {
        return Api::success([
            'home_score' => $match->home_score,
            'away_score' => $match->away_score,
            'scorers'    => $this->goals->listForMatch($match),
        ]);
    }

    /** POST /v1/matches/{match}/goals */
    public function store(StoreGoalRequest $request, MatchModel $match): JsonResponse
    {
        $scorer = $this->goals->record($match, $request->validated());
        $match->refresh();

        return Api::created(
            [
                'scorer_id'  => $scorer->id,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
            ],
            'Goal recorded successfully',
            ['match_id' => $match->id],
        );
    }

    /** DELETE /v1/goals/{scorer} */
    public function destroy(Request $request, Scorer $scorer): JsonResponse
    {
        $this->goals->remove($scorer, $request);

        return Api::success(null, 'Goal removed successfully');
    }

    /**
     * DELETE /v1/matches/{match}/goals/latest — undoes whichever goal was
     * scored most recently for this match. Backs the Rust media server's
     * "undoGoal" control action (see undoLastGoal in services.dart): the
     * app only tracks "last goal for this match", not a specific scorer_id,
     * so the caller can't target `destroy()` above directly.
     */
    public function destroyLatest(Request $request, MatchModel $match): JsonResponse
    {
        $scorer = $this->goals->removeLatestForMatch($match, $request);
        $match->refresh();

        if (! $scorer) {
            return Api::success(
                ['home_score' => $match->home_score, 'away_score' => $match->away_score],
                'No goal to undo for this match',
            );
        }

        return Api::success(
            ['scorer_id' => $scorer->id, 'home_score' => $match->home_score, 'away_score' => $match->away_score],
            'Goal undone successfully',
        );
    }
}
