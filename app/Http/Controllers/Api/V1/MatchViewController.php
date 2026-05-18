<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMatchViewRequest;
use App\Services\ApiResponseService as Api;
use App\Services\MatchViewService;
use Illuminate\Http\JsonResponse;

class MatchViewController extends Controller
{
    public function __construct(private MatchViewService $views) {}

    /** POST /v1/views */
    public function store(StoreMatchViewRequest $request): JsonResponse
    {
        $view = $this->views->create($request->validated());

        return Api::created($view, 'Match view recorded successfully', ['view_id' => $view->id]);
    }
}
