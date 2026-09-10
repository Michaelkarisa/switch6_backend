<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AwardBroadcasterRequest;
use App\Services\BroadcasterRankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BroadcasterRankController extends Controller
{
    public function __construct(private BroadcasterRankService $service)
    {
    }

    public function show(int $broadcasterId): JsonResponse
    {
        return response()->json($this->service->findOrDefault($broadcasterId));
    }

    public function highestViews(int $broadcasterId): JsonResponse
    {
        return response()->json([
            'broadcaster_id' => $broadcasterId,
            'highest_views'  => $this->service->highestViews($broadcasterId),
        ]);
    }

    public function award(AwardBroadcasterRequest $request, int $broadcasterId): JsonResponse
    {
        $point = $this->service->award($broadcasterId, (int) $request->input('current_views', 0));

        return response()->json([
            'broadcaster_id' => $broadcasterId,
            'awarded_point'  => $point,
        ]);
    }

    public function update(Request $request, int $broadcasterId): JsonResponse
    {
        $updated = $this->service->updatePoints($broadcasterId, (float) $request->input('points'));

        return response()->json(['updated' => $updated]);
    }

    public function destroy(int $broadcasterId): JsonResponse
    {
        $deleted = $this->service->delete($broadcasterId);

        return response()->json(['deleted' => $deleted]);
    }
}
