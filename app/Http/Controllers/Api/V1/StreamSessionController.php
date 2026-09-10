<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStreamSessionRequest;
use App\Http\Requests\UpdateStreamSessionRequest;
use App\Services\StreamSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class StreamSessionController extends Controller
{
    public function __construct(private StreamSessionService $service)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->service->all());
    }

    public function show(string $match_id): JsonResponse
    {
        $session = $this->service->findByMatch($match_id);

        if (!$session) {
            return response()->json(['error' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($session);
    }

    public function store(StoreStreamSessionRequest $request): JsonResponse
    {
        $session = $this->service->create($request->validated());

        return response()->json(['id' => $session->id], Response::HTTP_CREATED);
    }

    public function update(UpdateStreamSessionRequest $request, string $match_id): JsonResponse
    {
        $updated = $this->service->updateByMatch($match_id, $request->validated());

        return response()->json(['updated' => $updated]);
    }

    public function destroy(string $match_id): JsonResponse
    {
        $deleted = $this->service->deleteByMatch($match_id);

        return response()->json(['deleted' => $deleted]);
    }
}
