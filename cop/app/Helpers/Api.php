<?php

namespace App\Helpers;

use App\Services\ApiResponseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Static helper facade over ApiResponseService.
 * Used by Admin controllers that type-hint App\Helpers\Api.
 */
class Api
{
    public static function success(mixed $data, string $message = 'Success', array $meta = []): JsonResponse
    {
        return ApiResponseService::success($data, $message, $meta);
    }

    public static function created(mixed $data, string $message = 'Created', array $meta = []): JsonResponse
    {
        return ApiResponseService::created($data, $message, $meta);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $message = 'Success'): JsonResponse
    {
        return ApiResponseService::paginated($paginator, $message);
    }

    public static function batch(array $items, int $attempted, string $resource = 'items'): JsonResponse
    {
        return ApiResponseService::batch($items, $attempted, $resource);
    }

    public static function error(string $message, int $status = 400): JsonResponse
    {
        return ApiResponseService::error($message, $status);
    }

    public static function badRequest(string $message): JsonResponse
    {
        return ApiResponseService::badRequest($message);
    }

    public static function notFound(string $message = 'Not found'): JsonResponse
    {
        return ApiResponseService::notFound($message);
    }

    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return ApiResponseService::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return ApiResponseService::forbidden($message);
    }
}
