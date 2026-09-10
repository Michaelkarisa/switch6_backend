<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStreamEventRequest;
use App\Services\StreamEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class StreamEventController extends Controller
{
    public function __construct(private StreamEventService $service)
    {
    }

    public function index(string $match_id): JsonResponse
    {
        $events = $this->service->getForMatch($match_id);

        if ($events === null) {
            return response()->json(['error' => 'session not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($events);
    }

    public function store(StoreStreamEventRequest $request): JsonResponse
    {
        $event = $this->service->create($request->validated());

        return response()->json(['id' => $event->id], Response::HTTP_CREATED);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->service->delete($id);

        return response()->json(['deleted' => $deleted]);
    }
}
