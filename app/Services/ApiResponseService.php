<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApiResponseService
{
    public static function success(mixed $data, string $message = 'Success', array $meta = []): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, 200);
    }

    public static function created(mixed $data, string $message = 'Created', array $meta = []): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, 201);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ], 200);
    }

    public static function notFound(string $message = 'Not found'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
        ], 404);
    }

    public static function badRequest(string $message = 'Bad request'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
        ], 400);
    }

    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
        ], 403);
    }

    public static function error(string $message = 'Server error', int $code = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
        ], $code);
    }
}
