<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ApiResponseService as Api;
class ShareLineupController extends Controller
{
   public function index(Request $request): JsonResponse
    {
        return Api::success(
            'Share Lineup fetched successfully',
        );
    }

  public function show(Request $request): JsonResponse
    {
        return Api::success(
            'Share Lineup successfully',
        );
    }

     public function store(Request $request): JsonResponse
    {
        return Api::success(
            'Share Lineup stored successfully',
        );
    }
  public function destroy(Request $request): JsonResponse
    {
        return Api::success(
            'Share Lineup deleted successfully',
        );
    }
//
}
