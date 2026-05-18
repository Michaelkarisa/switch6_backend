<?php

namespace App\Services;

use App\Models\AdPayment;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlanSubscription;
use Illuminate\Support\Facades\DB;

class PlanSubscriptionService
{
    public function __construct(private AuditLogService $audit) {}

    /**
     * Subscribe a user to a plan.
     * If they already have an active subscription, it is cancelled first.
     */
    public function subscribe(User $user, Plan $plan, ?AdPayment $payment = null): UserPlanSubscription
    {
        return DB::transaction(function () use ($user, $plan, $payment) {
            // Cancel any currently active subscription
            UserPlanSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update([
                    'status'       => 'cancelled',
                    'cancelled_at' => now(),
                ]);

            $sub = UserPlanSubscription::create([
                'user_id'    => $user->id,
                'plan_id'    => $plan->id,
                'status'     => 'active',
                'starts_at'  => now(),
                'expires_at' => now()->addDays($plan->duration_days),
                'payment_id' => $payment?->id,
            ]);

            $this->audit->log(
                'subscribed',
                'plans',
                "User subscribed to plan: {$plan->name}",
                ['plan_id' => $plan->id, 'expires_at' => $sub->expires_at],
                $user,
                userId: $user->id,
            );

            return $sub->load('plan');
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
    public function current(User $user): ?UserPlanSubscription
    {
        return UserPlanSubscription::with('plan')
            ->where('user_id', $user->id)
            ->active()
            ->latest('starts_at')
            ->first();
    }

    /**
     * Full subscription history for a user.
     */
    public function history(User $user)
    {
        return UserPlanSubscription::with('plan')
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }
}