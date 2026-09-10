<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public webhook Safaricom's Daraja API calls once the customer has
 * entered (or cancelled/failed to enter) their M-Pesa PIN. This is the
 * ONLY place a payment actually becomes "completed" — everything before
 * this is just "the STK request was accepted".
 *
 * Not behind Sanctum auth (the provider can't authenticate that way);
 * instead it's only reachable at an unguessable callback URL you set in
 * config('services.mpesa.callback_url'), same as Daraja expects.
 */
class PaymentCallbackController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function mpesa(Request $request): JsonResponse
    {
        $payload = $request->all();
        $callback = data_get($payload, 'Body.stkCallback', []);
        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;

        if (! $checkoutRequestId) {
            Log::warning('mpesa callback: missing CheckoutRequestID', ['payload' => $payload]);

            // Always 200 back to Daraja — it retries on non-2xx, which
            // would just spam us with a payload we can never resolve.
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $payment = Payment::where('reference', $checkoutRequestId)->first();

        if (! $payment) {
            Log::warning('mpesa callback: no matching payment for reference', [
                'reference' => $checkoutRequestId,
            ]);

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $resultCode = $callback['ResultCode'] ?? 1;
        $status = ((int) $resultCode === 0) ? 'completed' : 'failed';

        $metadataItems = collect(data_get($callback, 'CallbackMetadata.Item', []))
            ->filter(fn ($item) => isset($item['Name']))
            ->mapWithKeys(fn ($item) => [$item['Name'] => $item['Value'] ?? null]);

        $transactionCode = $metadataItems->get('MpesaReceiptNumber');

        Transaction::create([
            'payment_id'       => $payment->id,
            'status'           => $status,
            'reference'        => $checkoutRequestId,
            'transaction_code' => $transactionCode,
            'provider'         => 'mpesa',
            'metadata'         => $payload,
        ]);

        $payment->paymentStatus();

        if ($status === 'completed') {
            $this->payments->confirm($payment, $transactionCode ?? $checkoutRequestId);
        } else {
            $this->payments->fail($payment, $callback['ResultDesc'] ?? 'Payment failed or was cancelled');
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
