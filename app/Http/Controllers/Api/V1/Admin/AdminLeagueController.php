<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Api;
use App\Http\Controllers\Controller;
use App\Models\League;
use App\Services\Admin\AdminLeagueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLeagueController extends Controller
{
    public function __construct(private readonly AdminLeagueService $leagues) {}

    public function index(Request $request): JsonResponse
    {
        return Api::paginated($this->leagues->paginate($request), 'Leagues fetched successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'leaguename' => 'nullable|string|max:255',
            'type'       => 'required|string|max:100',
        ]);

        return Api::created($this->leagues->store($data, $request), 'League created successfully');
    }

    public function update(Request $request, League $league): JsonResponse
    {
        $data = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'leaguename' => 'nullable|string|max:255',
            'type'       => 'sometimes|string|max:100',
        ]);

        return Api::success($this->leagues->update($league, $data, $request), 'League updated successfully');
    }

    public function destroy(Request $request, League $league): JsonResponse
    {
        $this->leagues->delete($league, $request);
        return Api::success(null, 'League deleted successfully');
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $league = League::onlyTrashed()->findOrFail($id);
        return Api::success($this->leagues->restore($league, $request), 'League restored successfully');
    }

    public function forceDestroy(Request $request, string $id): JsonResponse
    {
        $league = League::onlyTrashed()->findOrFail($id);
        $this->leagues->forceDelete($league, $request);
        return Api::success(null, 'League permanently deleted');
    }
}
