<?php

namespace App\Services\Admin;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlanSubscription;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminPlanService
{
    public function __construct(private AuditLogService $audit) {}

    public function listPlans(): \Illuminate\Support\Collection
    {
        return Plan::withCount('activeSubscriptions')->orderBy('price_kes')->get();
    }

    public function createPlan(array $data, ?Request $request = null): Plan
    {
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $plan = Plan::create($data);

        $this->audit->log('admin_plan_created', 'plans', 'Admin created plan',
            ['plan_id' => $plan->id, 'name' => $plan->name], $plan, $request);

        return $plan;
    }

    public function updatePlan(Plan $plan, array $data, ?Request $request = null): Plan
    {
        if (isset($data['name']) && ! isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $plan->update($data);

        $this->audit->log('admin_plan_updated', 'plans', 'Admin updated plan',
            ['plan_id' => $plan->id], $plan, $request);

        return $plan->fresh();
    }

    public function togglePlan(Plan $plan, ?Request $request = null): Plan
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        $this->audit->log(
            $plan->is_active ? 'admin_plan_activated' : 'admin_plan_deactivated',
            'plans', 'Admin toggled plan',
            ['plan_id' => $plan->id, 'is_active' => $plan->is_active], $plan, $request
        );

        return $plan->fresh();
    }

    public function deletePlan(Plan $plan, ?Request $request = null): void
    {
        if ($plan->activeSubscriptions()->exists()) {
            abort(response()->json([
                'success' => false,
                'message' => 'Cannot delete a plan with active subscriptions.',
                'data'    => null,
            ], 409));
        }

        $id = $plan->id;
        $plan->delete();

        $this->audit->log('admin_plan_deleted', 'plans', 'Admin deleted plan',
            ['plan_id' => $id], null, $request);
    }

    public function listSubscriptions(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return UserPlanSubscription::with(['user', 'plan'])
            ->when($request->query('status'),  fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('plan_id'), fn ($q, $p) => $q->where('plan_id', $p))
            ->when($request->query('user_id'), fn ($q, $u) => $q->where('user_id', $u))
            ->latest()
            ->paginate((int) $request->query('per_page', 20));
    }

    public function grantSubscription(User $user, Plan $plan, int $days, ?Request $request = null): UserPlanSubscription
    {
        return DB::transaction(function () use ($user, $plan, $days, $request) {
            UserPlanSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            $sub = UserPlanSubscription::create([
                'user_id'    => $user->id,
                'plan_id'    => $plan->id,
                'status'     => 'active',
                'starts_at'  => now(),
                'expires_at' => now()->addDays($days),
            ]);

            $this->audit->log('admin_subscription_granted', 'plans', 'Admin granted subscription',
                ['user_id' => $user->id, 'plan_id' => $plan->id, 'days' => $days], $user, $request);

            return $sub->load(['user', 'plan']);
        });
    }

    public function revokeSubscription(UserPlanSubscription $sub, ?Request $request = null): UserPlanSubscription
    {
        $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $this->audit->log('admin_subscription_revoked', 'plans', 'Admin revoked subscription',
            ['subscription_id' => $sub->id, 'user_id' => $sub->user_id], null, $request);

        return $sub->fresh();
    }

    public function extendSubscription(UserPlanSubscription $sub, int $days, ?Request $request = null): UserPlanSubscription
    {
        $newExpiry = max(now(), $sub->expires_at)->addDays($days);
        $sub->update(['expires_at' => $newExpiry, 'status' => 'active']);

        $this->audit->log('admin_subscription_extended', 'plans', 'Admin extended subscription',
            ['subscription_id' => $sub->id, 'added_days' => $days, 'new_expiry' => $newExpiry],
            null, $request);

        return $sub->fresh()->load(['user', 'plan']);
    }
}
