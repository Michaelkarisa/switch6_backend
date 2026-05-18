<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Services\Admin\AdminMatchService;
use App\Services\ApiResponseService as Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMatchController extends Controller
{
    public function __construct(private AdminMatchService $matches) {}

    public function index(Request $request): JsonResponse
    {
        return Api::paginated($this->matches->paginate($request), 'Matches fetched successfully');
    }

    public function forceDestroy(Request $request, MatchModel $match): JsonResponse
    {
        $id = $this->matches->forceDelete($match, $request);
        return Api::success(null, "Match {$id} permanently deleted");
    }

    public function reassign(Request $request, MatchModel $match): JsonResponse
    {
        $data = $request->validate([
            'author_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        return Api::success(
            $this->matches->reassignAuthor($match, $data['author_id'], $request),
            'Match author reassigned successfully'
        );
    }
}
