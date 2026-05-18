<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdPayment;
use App\Models\Plan;
use App\Models\UserPlanSubscription;
use App\Services\ApiResponseService as Api;
use App\Services\PlanSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function __construct(private PlanSubscriptionService $subscriptions) {}

    /** GET /v1/plans */
    public function index(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->orderBy('price_kes')->get();

        return Api::success($plans, 'Plans fetched successfully');
    }

    /** GET /v1/plans/{plan} */
    public function show(Plan $plan): JsonResponse
    {
        return Api::success($plan, 'Plan fetched successfully');
    }

    /** POST /v1/plans/{plan}/subscribe */
    public function subscribe(Request $request, Plan $plan): JsonResponse
    {
        $data = $request->validate([
            'payment_id' => ['sometimes', 'nullable', 'uuid', 'exists:ad_payments,id'],
        ]);

        $payment = isset($data['payment_id']) ? AdPayment::find($data['payment_id']) : null;

        $sub = $this->subscriptions->subscribe($request->user(), $plan, $payment);

        return Api::created($sub, 'Subscribed successfully', [
            'subscription_id' => $sub->id,
            'expires_at'      => $sub->expires_at,
        ]);
    }

    /** DELETE /v1/subscriptions/{subscription} */
    public function cancel(Request $request, UserPlanSubscription $subscription): JsonResponse
    {
        if ($subscription->user_id !== $request->user()->id) {
            return Api::forbidden('You do not own this subscription');
        }

        $sub = $this->subscriptions->cancel($subscription);

        return Api::success($sub, 'Subscription cancelled successfully');
    }

    /** GET /v1/subscriptions/current */
    public function current(Request $request): JsonResponse
    {
        $sub = $this->subscriptions->current($request->user());

        return Api::success($sub, 'Current subscription fetched successfully');
    }

    /** GET /v1/subscriptions/history */
    public function history(Request $request): JsonResponse
    {
        $history = $this->subscriptions->history($request->user());

        return Api::success($history, 'Subscription history fetched successfully', [
            'count' => $history->count(),
        ]);
    }
}
