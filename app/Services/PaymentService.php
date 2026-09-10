<?php

namespace App\Services;

use App\Mail\AdslotPaymentEmail;
use App\Mail\SubscriptionEmail;
use App\Models\Advertisement;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserPlanSubscription;
use App\Jobs\PaymentProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Creates payments, queues the provider (M-Pesa) request, and activates
 * whichever entity the payment is for (advertisement or subscription) once
 * the payment is confirmed. See PaymentCallbackController for the async
 * provider webhook that normally drives confirm()/fail().
 */
class PaymentService
{
    public function __construct(private AuditLogService $audit) {}

    public function pay(User $user, array $data): string
    {
        try {
            $payment = Payment::create([
                'user_id'           => $user->id,
                'amount'            => $data['amount'],
                'currency'          => $data['currency'] ?? 'KES',
                'payment_method'    => $data['method'] ?? 'mpesa',
                'reference'         => null,
                'transaction_code'  => null,
                'status'            => 'pending',
                'paid_at'           => null,
                'notes'             => null,
                'phone'             => $data['details']['phone'] ?? null,
                'type'              => $data['type'],
            ]);

            PaymentProcess::dispatch($payment->id)->onQueue('payment');

            $this->audit->log('queued', 'payments', 'Payment queued for provider request', [
                'payment_id' => $payment->id,
                'type'       => $data['type'],
            ], userId: $user->id);

            return $payment->id;
        } catch (\Throwable $e) {
            Log::error("payment error: {$e}");

            return '';
        }
    }

    public function forUser(User $user)
    {
        return Payment::where('user_id', $user->id)->latest()->get();
    }

    /**
     * Confirm a payment and activate whatever it belongs to
     * (advertisement, match bid, or subscription).
     */
    public function confirm(Payment $payment, string $transactionCode): Payment
    {
        DB::transaction(function () use ($payment, $transactionCode) {
            $payment->markPaid($transactionCode);
            $this->activateRelated($payment->fresh());
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

        // Reflect the failure on whatever this payment was for, so it
        // doesn't sit forever as "pending_payment" / "pending".
        Advertisement::where('payment_id', $payment->id)->update(['status' => 'payment_failed']);
        UserPlanSubscription::where('payment_id', $payment->id)->update(['status' => 'payment_failed']);
        $payment->matchBids()->update(['status' => 'refunded']);

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
     * Poll the payment's latest transaction and return the up-to-date model.
     * Mirrors the required "payment polls the latest transaction status"
     * behaviour for frontend polling.
     */
    public function refreshStatus(Payment $payment): Payment
    {
        $payment->paymentStatus();

        return $payment->fresh();
    }

    /**
     * List payments related to an advertisement (its own payment plus any
     * bid payments tied to it through match_bids).
     */
    public function forAdvertisement(Advertisement $ad)
    {
        $ids = collect([$ad->payment_id])
            ->merge($ad->bids()->pluck('payment_id'))
            ->filter()
            ->unique();

        return Payment::whereIn('id', $ids)->latest()->get();
    }

    private function activateRelated(Payment $payment): void
    {
        match ($payment->type) {
            'advertisement' => $this->activateAdvertisement($payment),
            'subscription'  => $this->activateSubscription($payment),
            default => Log::warning('Payment confirmed with unknown type', ['payment_id' => $payment->id, 'type' => $payment->type]),
        };
    }

    private function activateAdvertisement(Payment $payment): void
    {
        // Direct advertisement payment (general campaign).
        $ad = Advertisement::where('payment_id', $payment->id)->first();

        if ($ad) {
            $ad->update(['status' => 'active']);
            Mail::to($ad->user->email)->queue(new AdslotPaymentEmail($ad));
        }

        // Bid campaign: one payment can cover several match_bids rows.
        $bids = $payment->matchBids()->get();

        if ($bids->isNotEmpty()) {
            $bids->each(fn ($bid) => $bid->update(['status' => 'pending']));

            $bidAd = $bids->first()->ad;
            if ($bidAd && $bidAd->status !== 'active') {
                $bidAd->update(['status' => 'active']);
                Mail::to($bidAd->user->email)->queue(new AdslotPaymentEmail($bidAd));
            }
        }
    }

    private function activateSubscription(Payment $payment): void
    {
        $sub = UserPlanSubscription::where('payment_id', $payment->id)->first();

        if (! $sub) {
            return;
        }

        DB::transaction(function () use ($sub) {
            // Only now — once payment is confirmed — do we retire the
            // user's previous active subscription.
            UserPlanSubscription::where('user_id', $sub->user_id)
                ->where('status', 'active')
                ->where('id', '!=', $sub->id)
                ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            $sub->update([
                'status'     => 'active',
                'starts_at'  => now(),
                'expires_at' => now()->addDays($sub->plan->duration_days),
            ]);
        });

        Mail::to($sub->user->email)->queue(new SubscriptionEmail($sub->fresh()));
    }
}
