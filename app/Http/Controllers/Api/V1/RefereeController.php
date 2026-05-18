<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRefereeRequest;
use App\Models\Referee;
use App\Services\ApiResponseService as Api;
use App\Services\RefereeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefereeController extends Controller
{
    public function __construct(private RefereeService $referees) {}

    /** GET /v1/referees */
    public function index(Request $request): JsonResponse
    {
        return Api::success(
            $this->referees->list($request->query('search')),
            'Referees fetched successfully',
        );
    }

    /** POST /v1/referee */
    public function store(StoreRefereeRequest $request): JsonResponse
    {
        $referee = $this->referees->create($request->validated());

        return Api::created($referee, 'Referee added successfully', ['referee_id' => $referee->id]);
    }

    /** DELETE /v1/referees/{referee} */
    public function destroy(Referee $referee): JsonResponse
    {
        $this->referees->delete($referee);

        return Api::success(null, 'Referee deleted successfully');
    }
}
