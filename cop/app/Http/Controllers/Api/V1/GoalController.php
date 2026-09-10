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
}
