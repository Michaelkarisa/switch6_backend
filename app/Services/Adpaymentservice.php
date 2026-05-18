<?php

namespace App\Services;

use App\Models\AdPayment;
use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdPaymentService
{
    public function __construct(private AuditLogService $audit) {}

    /**
     * Initiate a payment record for an advertisement.
     * Call this before sending the STK push / payment request.
     */
    public function initiate(Advertisement $ad, User $user, array $data): AdPayment
    {
        $payment = AdPayment::create([
            'advertisement_id' => $ad->id,
            'user_id'          => $user->id,
            'amount_kes'       => $data['amount_kes'],
            'payment_method'   => $data['payment_method'] ?? 'mpesa',
            'mpesa_reference'  => $data['mpesa_reference'] ?? null,
            'status'           => 'pending',
        ]);

        $this->audit->log(
            'initiated',
            'ad_payments',
            'Ad payment initiated',
            ['payment_id' => $payment->id, 'amount_kes' => $payment->amount_kes],
            $user,
            userId: $user->id,
        );

        return $payment;
    }

    /**
     * Confirm a payment — called from M-Pesa / payment gateway callback.
     */
    public function confirm(AdPayment $payment, string $transactionCode): AdPayment
    {
        DB::transaction(function () use ($payment, $transactionCode) {
            $payment->markPaid($transactionCode);

            // Activate the advertisement once payment is confirmed
            $payment->advertisement->update(['status' => 'active']);
        });

        $this->audit->log(
            'confirmed',
            'ad_payments',
            'Ad payment confirmed',
            ['transaction_code' => $transactionCode, 'payment_id' => $payment->id],
            userId: $payment->user_id,
        );

        Log::info('AdPayment confirmed', [
            'payment_id'       => $payment->id,
            'transaction_code' => $transactionCode,
            'advertisement_id' => $payment->advertisement_id,
        ]);

        return $payment->fresh();
    }

    /**
     * Fail / reject a payment.
     */
    public function fail(AdPayment $payment, string $reason = ''): AdPayment
    {
        $payment->update([
            'status' => 'failed',
            'notes'  => $reason,
        ]);

        $this->audit->log(
            'failed',
            'ad_payments',
            'Ad payment failed',
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
        return AdPayment::where('advertisement_id', $ad->id)
            ->with('user')
            ->latest()
            ->get();
    }

    /**
     * List payments for a given user.
     */
    public function forUser(User $user)
    {
        return AdPayment::where('user_id', $user->id)
            ->with('advertisement')
            ->latest()
            ->get();
    }
}