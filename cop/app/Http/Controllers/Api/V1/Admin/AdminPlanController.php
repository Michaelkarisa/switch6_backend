<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlanSubscription;
use App\Services\Admin\AdminPlanService;
use App\Services\ApiResponseService as Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPlanController extends Controller
{
    public function __construct(private AdminPlanService $plans) {}

    public function index(Request $request): JsonResponse
    {
        return Api::paginated($this->plans->listPlans($request), 'Plans fetched successfully');
    }

    public function deleted(Request $request): JsonResponse
    {
        return Api::paginated($this->plans->listDeletedPlans($request), 'Deleted plans fetched successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'               => ['required', 'string', 'max:60'],
            'slug'               => ['sometimes', 'string', 'unique:plans,slug'],
            'description'        => ['nullable', 'string'],
            'price_kes'          => ['required', 'integer', 'min:0'],
            'duration_days'      => ['required', 'integer', 'min:1'],
            'max_matches'        => ['nullable', 'integer', 'min:1'],
            'max_cameras'        => ['nullable', 'integer', 'min:1'],
            'quality'            => ['nullable', 'integer', 'min:1'],
            'ads_enabled'        => ['boolean'],
            'analytics_enabled'  => ['boolean'],
            'is_active'          => ['boolean'],
            'features'           => ['nullable', 'array'],
        ]);

        $plan = $this->plans->createPlan($data, $request);
        return Api::created($plan, 'Plan created successfully', ['plan_id' => $plan->id]);
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $data = $request->validate([
            'name'              => ['sometimes', 'string', 'max:60'],
            'slug'              => ['sometimes', 'string', 'unique:plans,slug,' . $plan->id],
            'description'       => ['nullable', 'string'],
            'price_kes'         => ['sometimes', 'integer', 'min:0'],
            'duration_days'     => ['sometimes', 'integer', 'min:1'],
            'max_matches'       => ['nullable', 'integer', 'min:1'],
            'max_cameras'       => ['nullable', 'integer', 'min:1'],
            'quality'           => ['nullable', 'integer', 'min:1'],
            'ads_enabled'       => ['boolean'],
            'analytics_enabled' => ['boolean'],
            'features'          => ['nullable', 'array'],
        ]);

        return Api::success($this->plans->updatePlan($plan, $data, $request), 'Plan updated successfully');
    }

    public function toggle(Request $request, Plan $plan): JsonResponse
    {
        $plan = $this->plans->togglePlan($plan, $request);
        return Api::success($plan, $plan->is_active ? 'Plan activated' : 'Plan deactivated');
    }

    public function destroy(Request $request, Plan $plan): JsonResponse
    {
        $this->plans->deletePlan($plan, $request);
        return Api::success(null, 'Plan deleted successfully');
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $plan = Plan::onlyTrashed()->findOrFail($id);
        return Api::success($this->plans->restorePlan($plan, $request), 'Plan restored successfully');
    }

    public function forceDestroy(Request $request, string $id): JsonResponse
    {
        $plan = Plan::onlyTrashed()->findOrFail($id);
        $this->plans->forceDeletePlan($plan, $request);
        return Api::success(null, 'Plan permanently deleted');
    }

    public function subscriptions(Request $request): JsonResponse
    {
        return Api::paginated($this->plans->listSubscriptions($request), 'Subscriptions fetched successfully');
    }

    public function grant(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'uuid', 'exists:plans,id'],
            'days'    => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $sub = $this->plans->grantSubscription($user, Plan::findOrFail($data['plan_id']), $data['days'], $request);

        return Api::created($sub, 'Subscription granted successfully', [
            'subscription_id' => $sub->id,
            'expires_at'      => $sub->expires_at,
        ]);
    }

    public function revoke(Request $request, UserPlanSubscription $subscription): JsonResponse
    {
        return Api::success($this->plans->revokeSubscription($subscription, $request), 'Subscription revoked');
    }

    public function extend(Request $request, UserPlanSubscription $subscription): JsonResponse
    {
        $data = $request->validate(['days' => ['required', 'integer', 'min:1', 'max:3650']]);
        $sub  = $this->plans->extendSubscription($subscription, $data['days'], $request);
        return Api::success($sub, 'Subscription extended successfully', ['new_expiry' => $sub->expires_at]);
    }
}
