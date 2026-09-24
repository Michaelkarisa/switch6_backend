<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ApiResponseService as Api;
use App\Services\MatchBidService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchBidController extends Controller
{
    public function __construct(private MatchBidService $bids) {}

    /** GET /v1/match-bids/matches — bid tab: ranked, searchable matches */
    public function matches(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'date'     => ['nullable', 'date'],
            'stadium'  => ['nullable', 'string', 'max:150'],
            'club_id'  => ['nullable', 'uuid'],
        ]);

        $matches = $this->bids->eligibleMatches($filters);

        return Api::success($matches, 'Matches fetched successfully', [
            'count' => $matches->count(),
        ]);
    }

    /** GET /v1/match-bids/base-price?period=halftime */
    public function basePrice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period' => ['required', 'string', 'in:before_match,halftime,fulltime'],
        ]);

        return Api::success(['period' => $data['period'], 'base_price' => $this->bids->basePrice($data['period'])], 'Base price fetched successfully');
    }

    /** GET /v1/match-bids/status?match_id=&period= — current leading bid, minimum to outbid, and deadline */
    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'match_id' => ['required', 'uuid', 'exists:matches,id'],
            'period'   => ['required', 'string', 'in:before_match,halftime,fulltime'],
        ]);

        $status = $this->bids->auctionStatus($data['match_id'], $data['period']);

        return Api::success($status, 'Auction status fetched successfully');
    }

    /** POST /v1/match-bids */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'               => ['required', 'string', 'max:150'],
            'file_type'           => ['required', 'string', 'in:image'],
            'file'                => ['required_without:file_path', 'file', 'max:51200'], // 50MB
            'file_path'           => ['required_without:file', 'string'],
            'target_tags'         => ['nullable', 'array'],
            'currency'            => ['nullable', 'string', 'max:10'],
            'method'              => ['nullable', 'string', 'max:30'],
            'details'             => ['nullable', 'array'],
            'bids'                => ['required', 'array', 'min:1'],
            'bids.*.match_id'     => ['required', 'uuid', 'exists:matches,id'],
            'bids.*.period'       => ['required', 'string', 'in:before_match,halftime,fulltime'],
            'bids.*.amount'       => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->bids->createBidCampaign($request->user(), $data);

        return Api::success($result, 'Bid campaign created — complete the payment prompt to submit your bids');
    }

    /** GET /v1/match-bids/my */
    public function my(Request $request): JsonResponse
    {
        $bids = $this->bids->myBids($request->user());

        return Api::success($bids, 'Your bids fetched successfully', [
            'count' => $bids->count(),
        ]);
    }

    /** POST /v1/admin/match-bids/resolve — resolve a live match+period's slot auction */
    public function resolve(Request $request): JsonResponse
    {
        $data = $request->validate([
            'match_id' => ['required', 'uuid', 'exists:matches,id'],
            'period'   => ['required', 'string', 'in:before_match,halftime,fulltime'],
        ]);

        $result = $this->bids->resolveSlots($data['match_id'], $data['period']);

        return Api::success($result, 'Bid slots resolved successfully');
    }
}
