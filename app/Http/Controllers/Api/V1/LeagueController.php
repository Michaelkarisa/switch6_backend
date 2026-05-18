<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeagueRequest;
use App\Services\ApiResponseService as Api;
use App\Services\LeagueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function __construct(private LeagueService $leagues) {}

    /** GET /v1/leagues */
    public function index(Request $request): JsonResponse
    {
        return Api::success(
            $this->leagues->list($request->query('search')),
            'Leagues fetched successfully',
        );
    }

    /** POST /v1/league */
    public function store(StoreLeagueRequest $request): JsonResponse
    {
        $league = $this->leagues->create($request->validated());

        return Api::created($league, 'League added successfully', ['league_id' => $league->id]);
    }

    /** GET /v1/leagues/{identifier} */
    public function showByIdentifier(string $identifier): JsonResponse
    {
        $league = $this->leagues->findByIdentifier($identifier);

        if (! $league) {
            return Api::notFound('League not found');
        }

        return Api::success($league, 'League fetched successfully');
    }
}
