<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdPayment;
use App\Models\Advertisement;
use App\Services\AdPaymentService;
use App\Services\ApiResponseService as Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdPaymentController extends Controller
{
    public function __construct(private AdPaymentService $payments) {}

    /** POST /v1/ads/{advertisement}/payments */
    public function store(Request $request, Advertisement $advertisement): JsonResponse
    {
        $data = $request->validate([
            'amount_kes'      => ['required', 'integer', 'min:1'],
            'payment_method'  => ['sometimes', 'string', 'in:mpesa,card,bank_transfer'],
            'mpesa_reference' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $payment = $this->payments->initiate($advertisement, $request->user(), $data);

        return Api::created($payment, 'Payment initiated successfully', [
            'payment_id' => $payment->id,
        ]);
    }

    /** POST /v1/ads/payments/{payment}/confirm */
    public function confirm(Request $request, AdPayment $payment): JsonResponse
    {
        $data = $request->validate([
            'transaction_code' => ['required', 'string', 'max:100'],
        ]);

        $payment = $this->payments->confirm($payment, $data['transaction_code']);

        return Api::success($payment, 'Payment confirmed successfully');
    }

    /** POST /v1/ads/payments/{payment}/fail */
    public function fail(Request $request, AdPayment $payment): JsonResponse
    {
        $payment = $this->payments->fail($payment, $request->input('reason', ''));

        return Api::success($payment, 'Payment marked as failed');
    }

    /** GET /v1/ads/{advertisement}/payments */
    public function index(Advertisement $advertisement): JsonResponse
    {
        $payments = $this->payments->forAdvertisement($advertisement);

        return Api::success($payments, 'Payments fetched successfully', [
            'advertisement_id' => $advertisement->id,
            'count'            => $payments->count(),
        ]);
    }

    /** GET /v1/ads/payments/my */
    public function myPayments(Request $request): JsonResponse
    {
        $payments = $this->payments->forUser($request->user());

        return Api::success($payments, 'Your ad payments fetched successfully', [
            'count' => $payments->count(),
        ]);
    }
}
