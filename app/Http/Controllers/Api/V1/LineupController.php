<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLineupRequest;
use App\Models\MatchModel;
use App\Services\ApiResponseService as Api;
use App\Services\LineupService;
use Illuminate\Http\JsonResponse;

class LineupController extends Controller
{
    public function __construct(private LineupService $lineups) {}

    /** GET /v1/lineups/by-match/{match} */
    public function byMatch(MatchModel $match): JsonResponse
    {
        $lineups = $this->lineups->byMatch($match);

        return Api::success($lineups, 'Lineups fetched successfully', [
            'match_id' => $match->id,
            'count'    => $lineups->count(),
        ]);
    }

    /** POST /v1/lineups */
    public function storeMany(StoreLineupRequest $request): JsonResponse
    {
        $saved = $this->lineups->storeMany($request->validated());

        return Api::created($saved, 'Lineups saved successfully', ['count' => count($saved)]);
    }

    /** DELETE /v1/lineups/by-match/{match} */
    public function destroyByMatch(MatchModel $match): JsonResponse
    {
        $this->lineups->deleteByMatch($match);

        return Api::success(null, 'All lineups for match deleted successfully', [
            'match_id' => $match->id,
        ]);
    }
}
