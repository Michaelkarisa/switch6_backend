<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClubRequest;
use App\Models\Club;
use App\Services\ApiResponseService as Api;
use App\Services\ClubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClubController extends Controller
{
    public function __construct(private ClubService $clubs) {}

    /** GET /v1/clubs */
    public function index(Request $request): JsonResponse
    {
        return Api::success(
            $this->clubs->list($request->query('search')),
            'Clubs fetched successfully',
        );
    }

    /** POST /v1/clubs */
    public function store(StoreClubRequest $request): JsonResponse
    {
        $club = $this->clubs->create($request->validated());

        return Api::created($club, 'Club added successfully', ['club_id' => $club->id]);
    }

    /** GET /v1/club/{identifier} */
    public function showByIdentifier(string $identifier): JsonResponse
    {
        $club = $this->clubs->findByIdentifier($identifier);

        if (! $club) {
            return Api::notFound('Club not found');
        }

        return Api::success($club, 'Club fetched successfully');
    }

    /** DELETE /v1/clubs/{club} */
    public function destroy(Club $club): JsonResponse
    {
        $this->clubs->delete($club);

        return Api::success(null, 'Club deleted successfully');
    }
}
