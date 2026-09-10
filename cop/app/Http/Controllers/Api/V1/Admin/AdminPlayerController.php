<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Api;
use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Services\Admin\AdminPlayerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPlayerController extends Controller
{
    public function __construct(private readonly AdminPlayerService $players) {}

    public function index(Request $request): JsonResponse
    {
        return Api::paginated($this->players->paginate($request), 'Players fetched successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'club_id'       => 'required|uuid|exists:clubs,id',
            'position'      => 'required|string|max:50',
            'age'           => 'nullable|integer|min:15|max:50',
            'nationality'   => 'nullable|string|max:100',
            'jersey_number' => 'nullable|integer|min:1|max:99',
            'market_value'  => 'nullable|numeric|min:0',
        ]);

        return Api::created($this->players->store($data, $request), 'Player created successfully');
    }

    public function update(Request $request, Player $player): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'club_id'       => 'sometimes|uuid|exists:clubs,id',
            'position'      => 'sometimes|string|max:50',
            'age'           => 'nullable|integer|min:15|max:50',
            'nationality'   => 'nullable|string|max:100',
            'jersey_number' => 'nullable|integer|min:1|max:99',
            'market_value'  => 'nullable|numeric|min:0',
        ]);

        return Api::success($this->players->update($player, $data, $request), 'Player updated successfully');
    }

    public function destroy(Request $request, Player $player): JsonResponse
    {
        $this->players->delete($player, $request);
        return Api::success(null, 'Player deleted successfully');
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $player = Player::onlyTrashed()->findOrFail($id);
        return Api::success($this->players->restore($player, $request), 'Player restored successfully');
    }

    public function forceDestroy(Request $request, string $id): JsonResponse
    {
        $player = Player::onlyTrashed()->findOrFail($id);
        $this->players->forceDelete($player, $request);
        return Api::success(null, 'Player permanently deleted');
    }
}
