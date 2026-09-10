<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AdminUserService;
use App\Services\ApiResponseService as Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(private AdminUserService $users) {}

    public function index(Request $request): JsonResponse
    {
        return Api::paginated($this->users->paginate($request), 'Users fetched successfully');
    }

    public function show(User $user): JsonResponse
    {
        return Api::success($this->users->show($user), 'User fetched successfully');
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'phone'    => ['sometimes', 'string', 'unique:users,phone,' . $user->id],
            'role'     => ['sometimes', 'string'],
            'status'   => ['sometimes', 'in:active,suspended,inactive'],
            'password' => ['sometimes', 'string', 'min:8'],
            'camera'   => ['sometimes', 'integer'],
        ]);

        return Api::success($this->users->update($user, $data, $request), 'User updated successfully');
    }

    public function suspend(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return Api::badRequest('You cannot suspend yourself');
        }

        return Api::success($this->users->suspend($user, $request), 'User suspended successfully');
    }

    public function activate(Request $request, User $user): JsonResponse
    {
        return Api::success($this->users->activate($user, $request), 'User activated successfully');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return Api::badRequest('You cannot delete yourself');
        }

        $id = $this->users->delete($user, $request);

        return Api::success(null, "User {$id} deleted successfully");
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);

        return Api::success($this->users->restore($user, $request), 'User restored successfully');
    }

    public function forceDestroy(Request $request, string $id): JsonResponse
    {
        if ($id === $request->user()->id) {
            return Api::badRequest('You cannot permanently delete yourself');
        }

        $user = User::onlyTrashed()->findOrFail($id);
        $this->users->forceDelete($user, $request);

        return Api::success(null, "User {$id} permanently deleted");
    }

    public function impersonate(Request $request, User $user): JsonResponse
    {
        return Api::success(
            ['token' => $this->users->impersonateToken($user, $request)],
            'Impersonation token generated'
        );
    }

    public function loginHistory(User $user): JsonResponse
    {
        return Api::success($this->users->loginHistory($user), 'Login history fetched successfully');
    }
}
