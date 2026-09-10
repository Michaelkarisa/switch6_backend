<?php

namespace App\Services;

use App\Models\Login;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private AuditLogService $audit) {}

    public function register(array $data): array
    {
        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'status'   => 'active',
        ]);

        // Assign Spatie role based on submitted role field
        $role = $data['role'] ?? 'broadcaster';
        if (in_array($role, ['broadcaster', 'advertiser', 'admin', 'superadmin'], true)) {
            $user->assignRole($role);
        } else {
            $user->assignRole('broadcaster');
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $this->audit->log('registered', 'auth', 'User registered', [
            'email' => $user->email,
            'role'  => $user->role,
        ], $user, userId: $user->id);

        return [
            'token' => $token,
            'user'  => $this->userPayload($user),
        ];
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            $this->audit->log('failed_login', 'auth', 'Login failed', ['email' => $data['email']]);

            abort(response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'errors'  => null,
                'data'    => null,
            ], 401));
        }

        if ($user->status === 'suspended') {
            abort(response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Contact support.',
                'errors'  => null,
                'data'    => null,
            ], 403));
        }

        Login::create(['user_id' => $user->id]);

        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->audit->log('login', 'auth', 'User logged in', [], $user, userId: $user->id);

        return [
            'token' => $token,
            'user'  => $this->userPayload($user),
        ];
    }

    public function changePassword(User $user, array $data): void
    {
        if (! Hash::check($data['old_password'], $user->password)) {
            abort(response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
                'errors'  => null,
                'data'    => null,
            ], 401));
        }

        $user->update(['password' => Hash::make($data['new_password'])]);

        $this->audit->log('password_changed', 'auth', 'Password changed', [], $user, userId: $user->id);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();

        $this->audit->log('logout', 'auth', 'User logged out', [], $user, userId: $user->id);
    }

    public function deleteUser(User $user): void
    {
        $id    = $user->id;
        $email = $user->email;

        $user->tokens()->delete();
        $user->delete();

        $this->audit->log('deleted', 'users', 'User deleted', [
            'user_id' => $id,
            'email'   => $email,
        ]);
    }

    /**
     * Build the user payload returned after login/register.
     * Includes both the legacy `role` string AND the Spatie `roles` array
     * so the frontend can use either.
     */
    private function userPayload(User $user): array
    {
        return [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'phone'  => $user->phone,
            'role'   => $user->role,
            'roles'  => $user->getRoleNames()->toArray(),
            'status' => $user->status,
            'camera' => $this->cameras($user),
            'quality'=> $this->quality($user),
            'game_type' => $user->game_type,
        ];
    }

    private function cameras(User $user):int{
     $c = $user->cameras();
     return $c;
   }

    private function quality(User $user):int{
     $q = $user->quality();
     return $q;
   }
}
