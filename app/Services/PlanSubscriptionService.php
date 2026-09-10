<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlanSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PlanSubscriptionService
{
    public function __construct(private AuditLogService $audit, private PaymentService $payment) {}

    /**
     * Extra cost per quality tier above the plan's base price. 480p is
     * included free; each step up costs more. Video/stream quality on
     * the plan card checkboxes maps directly to this.
     */
    private const QUALITY_SURCHARGE = [
        480  => 0,
        720  => 100,
        1080 => 200,
        2160 => 300, // 4K
    ];

    /**
     * Subscribe a user to a plan. The subscription is created INACTIVE and
     * only flips to active once the payment is confirmed by the provider
     * callback (see PaymentService::activateSubscription()). Any currently
     * active subscription is only cancelled at that point too, so a
     * pending/failed payment never costs the user their current plan.
     */
    public function subscribe(User $user, array $data): array
    {
        $plan = Plan::findOrFail($data['plan_id']);
        $quality = (int) ($data['quality'] ?? 480);

        if (! array_key_exists($quality, self::QUALITY_SURCHARGE)) {
            throw ValidationException::withMessages([
                'quality' => ['Quality must be one of: '.implode(', ', array_keys(self::QUALITY_SURCHARGE)).'.'],
            ]);
        }

        return DB::transaction(function () use ($user, $plan, $data, $quality) {
            $amount = $plan->price + self::QUALITY_SURCHARGE[$quality];

            $pay = [
                'details'  => $data['details'] ?? [],
                'method'   => $data['method'] ?? 'mpesa',
                'type'     => 'subscription',
                'amount'   => $amount,
                'currency' => $plan->currency ?? 'KES',
            ];

            $paymentId = $this->payment->pay($user, $pay);

            if (! $paymentId) {
                throw ValidationException::withMessages([
                    'payment' => ['Could not initiate payment. Please try again.'],
                ]);
            }

            Log::info("subscription payment queued, payment_id: {$paymentId}");

            $sub = UserPlanSubscription::create([
                'user_id'    => $user->id,
                'plan_id'    => $plan->id,
                'payment_id' => $paymentId,
                'status'     => 'inactive', // becomes 'active' once payment confirms
                'starts_at'  => null,
                'expires_at' => null,
                'quality'    => $quality,
            ]);

            $this->audit->log(
                'subscription_requested',
                'plans',
                "User requested subscription to plan: {$plan->name}",
                ['plan_id' => $plan->id, 'quality' => $quality, 'amount' => $amount],
                $sub,
                userId: $user->id,
            );

            return [
                'message' => 'Subscription created — complete the payment prompt on your phone to activate it.',
                'data'    => $sub->load('plan')->toArray(),
            ];
        });
    }

    /**
     * Cancel a subscription immediately.
     */
    public function cancel(UserPlanSubscription $sub): UserPlanSubscription
    {
        $sub->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->audit->log(
            'cancelled',
            'plans',
            'Subscription cancelled',
            ['subscription_id' => $sub->id],
            userId: $sub->user_id,
        );

        return $sub->fresh();
    }

    /**
     * Return the current active subscription for a user, or null.
     */
    public function current(User $user): array
    {
        $userSub = UserPlanSubscription::with('plan')
            ->where('user_id', $user->id)
            ->active()
            ->latest('starts_at')
            ->first();

        return $userSub ? $userSub->toArray() : [];
    }

    /**
     * Full subscription history for a user.
     */
    public function history(User $user): array
    {
        return UserPlanSubscription::with('plan')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->toArray();
    }
}
