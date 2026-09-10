<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreViewRequest;
use App\Services\MatchViewsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class MatchViewsController extends Controller
{
    public function __construct(private MatchViewsService $service)
    {
    }

    public function index(string $match_id): JsonResponse
    {
        return response()->json($this->service->getForMatch($match_id));
    }

    public function total(string $match_id): JsonResponse
    {
        $total = $this->service->totalForMatch($match_id);

        return response()->json([
            'match_id'  => $match_id,
            'total_views' => $total,
        ]);
    }

    public function store(StoreViewRequest $request): JsonResponse
    {
        $sample = $this->service->create($request->validated());

        return response()->json(['id' => $sample->id], Response::HTTP_CREATED);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->service->delete($id);

        return response()->json(['deleted' => $deleted]);
    }
}
