<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Jobs\PaymentProcess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
/**
 * Dispatches audit writes to a background queue job so they never
 * block the HTTP response. Falls back to synchronous write on failure.
 */
class PaymentService
{

 public function __construct(private AuditLogService $audit) {}

    public function pay(
     User $user, array $data
    ): string {
        $id = (string) Str::uuid();
        try {
            $payment = Payment::create(
                [       'id'=> $id,
                        'user_id'=>$user->id,
                        'amount'=>$data['amount'],
                        'currency'=>$data['currency'],
                        'payment_method'=>$data['method'],
                        'reference'=>null,
                        'transaction_code'=>null,
                        'status'=>'pending',
                        'paid_at'=>null,
                        'notes'=>null,
                        'phone'=>$data['details']['phone'],
                        'type'=>$data['type'],
                ]
            );
            PaymentProcess::dispatch($payment->toArray())->onQueue('payment');
               // sent to queue payment add to auditlog
          return $id;
        } catch (\Throwable $e) {
         // failed to queue payment add to auditlog
         Log::error("payment error: {$e}");
         return "";
        }
    }

    public function forUser(User $user){
     // $payments = $user->payments();
      $payments = Payment::where('user_id',$user->id)->get();
      return $payments;
    }


        public function confirm(Payment $payment, string $transactionCode): Payment
    {
        DB::transaction(function () use ($payment, $transactionCode) {
            $payment->markPaid($transactionCode);

            // Activate the advertisement once payment is confirmed
            $payment->advertisement->update(['status' => 'active']);
        });

        $this->audit->log(
            'confirmed',
            'payments',
            'payment confirmed',
            ['transaction_code' => $transactionCode, 'payment_id' => $payment->id],
            userId: $payment->user_id,
        );

        Log::info('Payment confirmed', [
            'payment_id'       => $payment->id,
            'transaction_code' => $transactionCode,
        ]);

        return $payment->fresh();
    }

    /**
     * Fail / reject a payment.
     */
    public function fail(Payment $payment, string $reason = ''): Payment
    {
        $payment->update([
            'status' => 'failed',
            'notes'  => $reason,
        ]);

        $this->audit->log(
            'failed',
            'payments',
            'payment failed',
            ['payment_id' => $payment->id, 'reason' => $reason],
            userId: $payment->user_id,
        );

        return $payment->fresh();
    }

    /**
     * List payments for a given advertisement.
     */
    public function forAdvertisement(Advertisement $ad)
    {
        
        return $ad->payment()->get();
    }
}
