<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BatchStorePlayerRequest;
use App\Http\Requests\StorePlayerRequest;
use App\Models\Player;
use App\Services\ApiResponseService as Api;
use App\Services\PlayerService;
use Illuminate\Http\JsonResponse;

class PlayerController extends Controller
{
    public function __construct(private PlayerService $players) {}

    /** POST /v1/players */
    public function store(StorePlayerRequest $request): JsonResponse
    {
        $player = $this->players->create($request->validated());

        return Api::created($player, 'Player added successfully', ['player_id' => $player->id]);
    }

    /** POST /v1/players/batch */
    public function batchStore(BatchStorePlayerRequest $request): JsonResponse
    {
        $items = $request->validated();
        $added = $this->players->createMany($items);

        return Api::batch($added, count($items), 'players');
    }

    /** DELETE /v1/players/{player} */
    public function destroy(Player $player): JsonResponse
    {
        $this->players->delete($player);

        return Api::success(null, 'Player deleted successfully');
    }
}
