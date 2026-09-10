<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Api;
use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Services\Admin\AdminClubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminClubController extends Controller
{
    public function __construct(private readonly AdminClubService $clubs) {}

    public function index(Request $request): JsonResponse
    {
        return Api::paginated($this->clubs->paginate($request), 'Clubs fetched successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'city'         => 'nullable|string|max:255',
            'founded_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'stadium'      => 'nullable|string|max:255',
            'manager'      => 'nullable|string|max:255',
            'jersey_color' => 'nullable|integer',
        ]);

        return Api::created($this->clubs->store($data, $request), 'Club created successfully');
    }

    public function update(Request $request, Club $club): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'city'         => 'nullable|string|max:255',
            'founded_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'stadium'      => 'nullable|string|max:255',
            'manager'      => 'nullable|string|max:255',
            'jersey_color' => 'nullable|integer',
        ]);

        return Api::success($this->clubs->update($club, $data, $request), 'Club updated successfully');
    }

    public function destroy(Request $request, Club $club): JsonResponse
    {
        $this->clubs->delete($club, $request);
        return Api::success(null, 'Club deleted successfully');
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $club = Club::onlyTrashed()->findOrFail($id);
        return Api::success($this->clubs->restore($club, $request), 'Club restored successfully');
    }

    public function forceDestroy(Request $request, string $id): JsonResponse
    {
        $club = Club::onlyTrashed()->findOrFail($id);
        $this->clubs->forceDelete($club, $request);
        return Api::success(null, 'Club permanently deleted');
    }
}
