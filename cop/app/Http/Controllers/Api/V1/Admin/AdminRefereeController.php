<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Api;
use App\Http\Controllers\Controller;
use App\Models\Referee;
use App\Services\Admin\AdminRefereeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRefereeController extends Controller
{
    public function __construct(private readonly AdminRefereeService $referees) {}

    public function index(Request $request): JsonResponse
    {
        return Api::paginated($this->referees->paginate($request), 'Referees fetched successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'nationality' => 'nullable|string|max:100',
        ]);

        return Api::created($this->referees->store($data, $request), 'Referee created successfully');
    }

    public function update(Request $request, Referee $referee): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'nationality' => 'nullable|string|max:100',
        ]);

        return Api::success($this->referees->update($referee, $data, $request), 'Referee updated successfully');
    }

    public function destroy(Request $request, Referee $referee): JsonResponse
    {
        $this->referees->delete($referee, $request);
        return Api::success(null, 'Referee deleted successfully');
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $referee = Referee::onlyTrashed()->findOrFail($id);
        return Api::success($this->referees->restore($referee, $request), 'Referee restored successfully');
    }

    public function forceDestroy(Request $request, string $id): JsonResponse
    {
        $referee = Referee::onlyTrashed()->findOrFail($id);
        $this->referees->forceDelete($referee, $request);
        return Api::success(null, 'Referee permanently deleted');
    }
}
