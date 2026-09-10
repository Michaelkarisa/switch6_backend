<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlanSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Request;

class PlanSubscriptionService
{
    public function __construct(private AuditLogService $audit, private PaymentService $payment) {}

    /**
     * Subscribe a user to a plan.
     * If they already have an active subscription, it is cancelled first.
     */
    public function subscribe(User $user,array $data): array
    {
       // if($user->elligible()){
        //     return [
        //       'message'=> 'On Pro plan 60 day free trial',
        //       'data'=>[...$user->currentPlan()->toArray(),'default_quality'=>720]
        //     ];
        //}
        $plan = Plan::find($data['plan_id']);
        return DB::transaction(function () use ($user,$plan,$data) {
            // Cancel any currently active subscription
            UserPlanSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update([
                    'status'       => 'cancelled',
                    'cancelled_at' => now(),
                ]);
                 $qPrice = [
                    480=> 100,
                    720=> 200,
                    1080=> 300,
                    2160=> 400,
                   ]; 
                $pay = [
                    'details'=>$data['details'],
                    'method'=>$data['method'],
                    'type'=> 'subscription',
                    'amount'=> ($plan->price)+$qPrice[$data['quality']],
                    'currency'=> $plan->currency??'KES',
                ];
              
            $payment_id = $this->payment->pay($user,$pay);
            Log::info("payment_id: {$payment_id}");
            $sub = UserPlanSubscription::create([
                'user_id'    => $user->id,
                'plan_id'    => $plan->id,
                'payment_id' => null,
                'status'     => 'active',
                'starts_at'  => now(),
                'expires_at' => now()->addDays($plan->duration_days),
                'quality' => $data['quality']
            ]);

            $this->audit->log(
                'subscribed',
                'plans',
                "User subscribed to plan: {$plan->name}",
                ['plan_id' => $plan->id, 'expires_at' => $sub->expires_at],
                $user,
                userId: $user->id,
            );
              $resp = [
               'message'=> 'Subscribed successfully',
               'data'=>$sub->load('plan')->toArray()
              ];
            return $resp;
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
            if($userSub){
                return $userSub->toArray();
            }
        return [];
    }

    /**
     * Full subscription history for a user.
     */
    public function history(User $user):array
    {
        $userHistory = UserPlanSubscription::with('plan')
            ->where('user_id', $user->id)
            ->latest()
            ->get();
            if($userHistory){
                return $userHistory->toArray();
            }
        return[];
    }
}