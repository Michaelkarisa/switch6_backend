<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    public function __construct(private AuditLogService $audit) {}

    public function paginate(Request $request): LengthAwarePaginator
    {
        return User::query()
            ->when($request->query('search'), fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
            )
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('role'),   fn ($q, $r) => $q->where('role', $r))
            ->withCount(['matches', 'subscriptions'])
            ->latest()
            ->paginate((int) $request->query('per_page', 20));
    }

    public function show(User $user): User
    {
        return $user->loadMissing([
            'subscriptions.plan',
            'matches' => fn ($q) => $q->latest()->limit(10),
        ]);
    }

    public function update(User $user, array $data, ?Request $request = null): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Sync Spatie role if role field is changing
        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        $user->update($data);

        $this->audit->log('admin_user_updated', 'users', 'Admin updated user',
            ['user_id' => $user->id, 'fields' => array_keys($data)], $user, $request);

        return $user->fresh();
    }

    public function suspend(User $user, ?Request $request = null): User
    {
        $user->update(['status' => 'suspended']);
        $user->tokens()->delete();

        $this->audit->log('admin_user_suspended', 'users', 'Admin suspended user',
            ['user_id' => $user->id], $user, $request);

        return $user->fresh();
    }

    public function activate(User $user, ?Request $request = null): User
    {
        $user->update(['status' => 'active']);

        $this->audit->log('admin_user_activated', 'users', 'Admin activated user',
            ['user_id' => $user->id], $user, $request);

        return $user->fresh();
    }

    public function delete(User $user, ?Request $request = null): string
    {
        $id = $user->id;
        $user->tokens()->delete();
        $user->delete();

        $this->audit->log('admin_user_deleted', 'users', 'Admin deleted user',
            ['user_id' => $id], null, $request);

        return $id;
    }

    public function impersonateToken(User $user, ?Request $request = null): string
    {
        $token = $user->createToken('admin-impersonate')->plainTextToken;

        $this->audit->log('admin_impersonated', 'users', 'Admin generated impersonation token',
            ['target_user_id' => $user->id], $user, $request);

        return $token;
    }

    public function loginHistory(User $user): \Illuminate\Support\Collection
    {
        return $user->logins()->latest()->limit(50)->get();
    }
}
