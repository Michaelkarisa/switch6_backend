<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ShareLineup;
use App\Services\ApiResponseService as Api;
use App\Services\ShareLineupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareLineupController extends Controller
{
    public function __construct(private ShareLineupService $shareLineups) {}

    /** POST /v1/share-lineups */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'match_id'     => ['required', 'uuid', 'exists:matches,id'],
            'club_id'      => ['required', 'uuid', 'exists:clubs,id'],
            'recepient_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $share = $this->shareLineups->shareLineup($request->user(), $data);

        return Api::success($share, 'Lineup shared successfully');
    }

    /** GET /v1/share-lineups — lineups shared with me */
    public function index(Request $request): JsonResponse
    {
        $shares = $this->shareLineups->listForRecipient($request->user());

        return Api::success($shares, 'Shared lineups fetched successfully', [
            'count' => $shares->count(),
        ]);
    }

    /** GET /v1/share-lineups/sent — lineups I've shared */
    public function sent(Request $request): JsonResponse
    {
        $shares = $this->shareLineups->listSent($request->user());

        return Api::success($shares, 'Sent lineups fetched successfully', [
            'count' => $shares->count(),
        ]);
    }

    /** GET /v1/share-lineups/{shareLineup}/preview */
    public function preview(ShareLineup $shareLineup): JsonResponse
    {
        $players = $this->shareLineups->preview($shareLineup);

        return Api::success($players, 'Shared lineup preview fetched successfully', [
            'count' => $players->count(),
        ]);
    }

    /** POST /v1/share-lineups/{shareLineup}/import */
    public function import(Request $request, ShareLineup $shareLineup): JsonResponse
    {
        $data = $request->validate([
            'match_id' => ['required', 'uuid', 'exists:matches,id'],
        ]);

        $lineups = $this->shareLineups->import($request->user(), $shareLineup, $data['match_id']);

        return Api::success($lineups, 'Lineup imported successfully', [
            'count' => count($lineups),
        ]);
    }

    /** DELETE /v1/share-lineups/{shareLineup} */
    public function destroy(Request $request, ShareLineup $shareLineup): JsonResponse
    {
        $this->shareLineups->destroy($request->user(), $shareLineup);

        return Api::success(null, 'Shared lineup removed successfully');
    }

    /** GET /v1/broadcasters/search?q= — picker used by the "share lineup" prompt */
    public function searchBroadcasters(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');

        $broadcasters = $this->shareLineups->searchBroadcasters($query, $request->user()->id);

        return Api::success($broadcasters, 'Broadcasters fetched successfully', [
            'count' => $broadcasters->count(),
        ]);
    }
}
