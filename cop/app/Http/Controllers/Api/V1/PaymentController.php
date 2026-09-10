<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Advertisement;
use App\Services\PaymentService;
use App\Services\ApiResponseService as Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    /**Post /v1/payments */

    public function store(Request $request){

     $response = $this->payments->pay($request->user(),$request->toArray());
      return Api::success($response, 'Payment sent successfully',[
        'payment_id'=> $response,
      ]);
    }

    /** POST /v1/payments/{payment}/confirm */
    public function confirm(Request $request, Payment $payment): JsonResponse
    {
        $data = $request->validate([
            'transaction_code' => ['required', 'string', 'max:100'],
        ]);

        $payment = $this->payments->confirm($payment, $data['transaction_code']);

        return Api::success($payment, 'Payment confirmed successfully');
    }

    /** POST /v1/payments/{payment}/fail */
    public function fail(Request $request, Payment $payment): JsonResponse
    {
        $payment = $this->payments->fail($payment, $request->input('reason', ''));

        return Api::success($payment, 'Payment marked as failed');
    }

    /** GET /v1/ads/{advertisement}/payments */
    public function index(string $id): JsonResponse
    {
        $payments = Payment::find($id);

        return Api::success($payments, 'Payments fetched successfully', [
            'count'  => $payments->count(),
        ]);
    }

    /** GET /v1/payments/my */
    public function myPayments(Request $request): JsonResponse
    {
        $payments = $this->payments->forUser($request->user());

        return Api::success($payments, 'Your payments fetched successfully', [
            'count' => $payments->count(),
        ]);
    }
}
