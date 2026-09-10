<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlanSubscription;
use App\Services\ApiResponseService as Api;
use App\Services\PlanSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PlanController extends Controller
{
    public function __construct(private PlanSubscriptionService $subscriptions) {}

    /** GET /v1/plans */
    public function index(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->orderBy('price')->get();

        return Api::success($plans, 'Plans fetched successfully');
    }

    /** GET /v1/plans/{plan} */
    public function show(Plan $plan): JsonResponse
    {
        return Api::success($plan, 'Plan fetched successfully');
    }

    /** POST /v1/plans/{plan}/subscribe */
    public function subscribe(Request $request,): JsonResponse
    {
       // $data = $request->validate([
       //     'plan_id' => ['sometimes', 'nullable', 'uuid', 'exists:plans,id'],
       //     'plan_id' => ['sometimes', 'nullable', 'uuid', 'exists:plans,id'],
        //    'plan_id' => ['sometimes', 'nullable', 'uuid', 'exists:plans,id'],
        //]);

       // $payment = isset($data['payment_id']) ? Payment::find($data['payment_id']) : null;

        $sub = $this->subscriptions->subscribe($request->user(),$request->toArray());
       //Log::info("request",$request->toArray());
        return Api::created($sub, $sub['message']);
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
           // 'count' => $history->a,
        ]);
    }
}
