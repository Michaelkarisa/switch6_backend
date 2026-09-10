<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Services\ApiResponseService as Api;
use App\Services\MatchRevenueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backs the broadcaster "Revenue" tab — how much each match's ad slots
 * generated and how much the broadcaster expects to get paid.
 */
class RevenueController extends Controller
{
    public function __construct(private MatchRevenueService $revenue) {}

    /** GET /v1/revenue — summary + per-match breakdown for the logged-in broadcaster */
    public function index(Request $request): JsonResponse
    {
        $summary = $this->revenue->summaryForBroadcaster($request->user());

        return Api::success($summary, 'Revenue summary fetched successfully');
    }

    /** GET /v1/revenue/matches/{match} — breakdown for one match */
    public function forMatch(MatchModel $match): JsonResponse
    {
        $rows = $this->revenue->forMatch($match);

        return Api::success($rows, 'Match revenue fetched successfully', [
            'count' => $rows->count(),
        ]);
    }
}
