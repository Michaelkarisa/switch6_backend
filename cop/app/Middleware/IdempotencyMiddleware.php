<?php

namespace App\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply idempotency to POST requests
        if (! $request->isMethod('post')) {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if (! $idempotencyKey) {
            return response()->json([
                'success' => false,
                'message' => 'Idempotency-Key header is required for POST requests.',
                'data' => null,
                'meta' => [],
            ], 400);
        }

        if (! Str::isUuid($idempotencyKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Idempotency-Key must be a valid UUID.',
                'data' => null,
                'meta' => [],
            ], 422);
        }

        $userId = auth()->id();

        $existingRecord = IdempotencyKey::query()
            ->where('id', $idempotencyKey)
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when(! $userId, fn ($query) => $query->whereNull('user_id'))
            ->first();

        if ($existingRecord) {
            return response()->json(
                json_decode($existingRecord->response, true),
                $existingRecord->status_code ?? 200
            );
        }

        $response = $next($request);

        // Only cache JSON responses
        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $responseData = $response->getData(true);
        $statusCode = $response->getStatusCode();

        try {
            IdempotencyKey::create([
                'id' => $idempotencyKey,
                'user_id' => $userId,
                'response' => json_encode($responseData),
                'status_code' => $statusCode,
            ]);
        } catch (QueryException $exception) {
            // Handles race conditions where the same key is inserted twice nearly at once
            $existingRecord = IdempotencyKey::query()
                ->where('id', $idempotencyKey)
                ->when($userId, fn ($query) => $query->where('user_id', $userId))
                ->when(! $userId, fn ($query) => $query->whereNull('user_id'))
                ->first();

            if ($existingRecord) {
                return response()->json(
                    json_decode($existingRecord->response, true),
                    $existingRecord->status_code ?? 200
                );
            }

            throw $exception;
        }

        return $response;
    }
}