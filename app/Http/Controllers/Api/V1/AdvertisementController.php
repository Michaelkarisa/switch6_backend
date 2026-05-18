<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Services\AdvertisementService;
use App\Services\ApiResponseService as Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    public function __construct(private AdvertisementService $service) {}

    /** GET /v1/ads */
    public function index(Request $request): JsonResponse
    {
        return Api::success(
            $this->service->listForUser($request->user()),
            'Advertisements fetched successfully'
        );
    }

    /** POST /v1/ads */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'file_type' => ['required', 'in:image,video'],
            'file'      => ['required_without:file_path', 'file', 'max:51200'], // 50MB
            'file_path' => ['required_without:file', 'string'],
            'duration'  => ['sometimes', 'integer', 'min:1', 'max:300'],
            'period'    => ['nullable', 'string'],
            'end_date'  => ['nullable', 'date'],
        ]);

        $ad = $this->service->create($request->all(), $request->user());

        return Api::created($ad, 'Advertisement created successfully', ['advertisement_id' => $ad->id]);
    }

    /** GET /v1/ads/{advertisement} */
    public function show(Advertisement $advertisement): JsonResponse
    {
        return Api::success($advertisement, 'Advertisement fetched successfully');
    }

    /** DELETE /v1/ads/{advertisement} */
    public function destroy(Request $request, Advertisement $advertisement): JsonResponse
    {
        // Owners and admins may delete
        $user = $request->user();
        if ($advertisement->user_id !== $user->id && ! $user->hasRole('admin') && ! $user->hasRole('superadmin')) {
            return Api::forbidden('You do not own this advertisement');
        }

        $this->service->delete($advertisement, $user);

        return Api::success(null, 'Advertisement deleted successfully');
    }

    /** GET /v1/ads/select */
    public function select(Request $request): JsonResponse
    {
        $ad = $this->service->selectForMatch(
            $request->query('match_id'),
            $request->query('period'),
        );

        if (! $ad) {
            return Api::notFound('No eligible advertisement found');
        }

        return Api::success($ad, 'Advertisement selected for stream injection');
    }
}
