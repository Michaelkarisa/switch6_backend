<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdPayment;
use App\Models\Advertisement;
use App\Services\Admin\AdminAdvertisementService;
use App\Services\ApiResponseService as Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAdvertisementController extends Controller
{
    public function __construct(private AdminAdvertisementService $service) {}

    public function index(Request $request): JsonResponse
    {
        return Api::paginated($this->service->paginate($request), 'Advertisements fetched successfully');
    }

    public function update(Request $request, Advertisement $advertisement): JsonResponse
    {
        $data = $request->validate([
            'title'       => ['sometimes', 'string', 'max:255'],
            'file_type'   => ['sometimes', 'in:image,video'],
            'file_path'   => ['sometimes', 'string'],
            'duration'    => ['sometimes', 'integer', 'min:1'],
            'period'      => ['nullable', 'string'],
            'end_date'    => ['nullable', 'date'],
            'target_tags' => ['nullable', 'array'],
        ]);

        return Api::success($this->service->update($advertisement, $data, $request), 'Advertisement updated');
    }

    public function setStatus(Request $request, Advertisement $advertisement): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:active,paused,expired']]);
        return Api::success($this->service->setStatus($advertisement, $data['status'], $request), 'Status updated');
    }

    public function destroy(Request $request, Advertisement $advertisement): JsonResponse
    {
        $this->service->delete($advertisement, $request);
        return Api::success(null, 'Advertisement deleted successfully');
    }

    public function analytics(Advertisement $advertisement): JsonResponse
    {
        return Api::success($this->service->analytics($advertisement), 'Analytics fetched successfully');
    }

    public function payments(Request $request): JsonResponse
    {
        return Api::paginated($this->service->paginatePayments($request), 'Payments fetched successfully');
    }

    public function revenue(): JsonResponse
    {
        return Api::success($this->service->revenueBreakdown(), 'Revenue fetched successfully');
    }

    public function confirmPayment(Request $request, AdPayment $payment): JsonResponse
    {
        $data = $request->validate(['transaction_code' => ['required', 'string', 'max:100']]);
        return Api::success($this->service->confirmPayment($payment, $data['transaction_code'], $request), 'Payment confirmed');
    }

    public function refundPayment(Request $request, AdPayment $payment): JsonResponse
    {
        return Api::success($this->service->refundPayment($payment, $request), 'Payment refunded');
    }
}
