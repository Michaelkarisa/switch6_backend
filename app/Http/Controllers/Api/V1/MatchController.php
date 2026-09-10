<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMatchRequest;
use App\Http\Requests\UpdateMatchStatusRequest;
use App\Http\Requests\UpdateMatchRequest;
use App\Models\MatchModel;
use App\Services\ApiResponseService as Api;
use App\Services\MatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function __construct(private MatchService $matches) {}

    /** GET /v1/matches */
    public function index(): JsonResponse
    {
        $items = $this->matches->listFormatted();

        if ($items->isEmpty()) {
            return Api::notFound('No matches found');
        }

        return Api::success($items, 'Matches fetched successfully');
    }

    /** GET /v1/matches/paginated */
    public function paginated(Request $request): JsonResponse
    {
        $limit  = (int) $request->query('limit', 20);
        $offset = (int) $request->query('offset', 0);

        $items = $this->matches->paginatedFormatted($limit, $offset);

        return Api::success($items, 'Matches fetched successfully', [
            'limit'  => $limit,
            'offset' => $offset,
            'count'  => count($items),
        ]);
    }

    /** POST /v1/matches */
    public function store(StoreMatchRequest $request): JsonResponse
    {
        $result = $this->matches->create(
            $request->validated(),
            $request->user()?->id,
            $request,
        );

        return Api::created(
            $result['formatted'],
            'Match added successfully',
            ['match_id' => $result['match']->id],
        );
    }

    /** GET /v1/matches/{match} */
    public function show(string $id): JsonResponse
    {     $match = MatchModel::Where('id',$id)->orWhere('slug',$id)->first();
          if($match == null){
           return Api::error('Match Not Found',404);
          }
        return Api::success($this->matches->format($match), 'Match fetched successfully');
    }

    /** GET /v1/matches/status/{status} */
    public function byStatus(string $status): JsonResponse
    {
        $items = $this->matches->byStatus($status);

        if ($items->isEmpty()) {
            return Api::notFound("No matches found with status: {$status}");
        }

        return Api::success($items, 'Matches fetched successfully', ['status' => $status]);
    }

    /** GET /v1/matches/league/{league} */
    public function byLeague(string $leagueName): JsonResponse
    {
        $items = $this->matches->byLeague($leagueName);

        if ($items->isEmpty()) {
            return Api::notFound("No matches found for league: {$leagueName}");
        }

        return Api::success($items, 'Matches fetched successfully', ['league' => $leagueName]);
    }

    /** GET /v1/mymatches/{author} */
    public function byAuthor(string $author): JsonResponse
    {
        $items = $this->matches->byAuthor($author);

        if ($items->isEmpty()) {
            return Api::notFound("No matches found for author: {$author}");
        }

        return Api::success($items, 'Matches fetched successfully');
    }

    /** GET /v1/matches/live */
    public function live(): JsonResponse
    {
        return $this->byStatus('live');
    }

    /** GET /v1/matches/upcoming */
    public function upcoming(): JsonResponse
    {
        return $this->byStatus('scheduled');
    }

    /** GET /v1/matches/completed */
    public function completed(): JsonResponse
    {
        return $this->byStatus('finished');
    }

    /** POST /v1/matches/{match}/status */
    public function updateStatus(UpdateMatchStatusRequest $request, MatchModel $match): JsonResponse
    {
        $match = $this->matches->updateStatus(
            $match,
            $request->validated('status'),
            $request,
        );

        return Api::success(
            ['match_id' => $match->id, 'status' => $match->status],
            'Match status updated successfully',
        );
    }

    /** DELETE /v1/delete-matches/{match} */
    public function destroy(MatchModel $match, Request $request): JsonResponse
    {
        $id = $this->matches->delete($match, $request);

        return Api::success(null, "Match {$id} deleted successfully");
    }

    public function update(UpdateMatchRequest $request, MatchModel $match): JsonResponse
    {
        $data = $request->validated();

        $updated = $this->matches->update($match, $data, $request);

        return Api::success($updated, 'Match updated successfully');
    }
}
