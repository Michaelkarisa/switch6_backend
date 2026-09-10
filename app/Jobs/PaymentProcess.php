<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Transaction;
use App\Services\MpesaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends the STK push request to the payment provider for an already-created
 * Payment record, logging a Transaction row for every status change so the
 * payment's status can always be derived from "the latest transaction".
 *
 * Final success/failure does NOT happen here — that arrives asynchronously
 * via PaymentCallbackController once the customer enters their PIN. This
 * job only covers "was the STK request itself accepted".
 */
class PaymentProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(private readonly string $paymentId) {}

    public function handle(MpesaService $mpesa): void
    {
        $payment = Payment::find($this->paymentId);

        if (! $payment) {
            Log::error('PaymentProcess: payment not found', ['payment_id' => $this->paymentId]);

            return;
        }

        $this->logTransaction($payment, 'queued', ['message' => 'Payment queued for provider request']);

        $result = $mpesa->stkPush($payment);

        if ($result['ok']) {
            if ($result['reference']) {
                $payment->update(['reference' => $result['reference']]);
            }
            // Status stays "pending" here — the customer hasn't entered
            // their PIN yet. Final status arrives via the callback.
            $this->logTransaction($payment, 'requested', $result['raw'], $result['reference']);
        } else {
            $this->logTransaction($payment, 'failed', $result['raw']);
        }
    }

    private function logTransaction(Payment $payment, string $status, array $metadata = [], ?string $reference = null): void
    {
        try {
            Transaction::create([
                'payment_id' => $payment->id,
                'status'     => $status,
                'reference'  => $reference,
                'provider'   => 'mpesa',
                'metadata'   => $metadata,
            ]);
        } catch (\Throwable $e) {
            Log::error('Transaction log failed', [
                'error'   => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('payment job failed', [
            'payment_id' => $this->paymentId,
            'error'      => $exception->getMessage(),
        ]);

        if ($payment = Payment::find($this->paymentId)) {
            $this->logTransaction($payment, 'failed', ['error' => $exception->getMessage()]);
        }
    }
}
