<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\ApiResponseService as Api;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    /** POST /v1/auth/register */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->validated());

        return Api::created($result['user'], 'Registration successful', [
            'token' => $result['token'],
        ]);
    }

    /** POST /v1/auth/login */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->auth->login($request->only('email', 'password'));

        return Api::success($result['user'], 'Login successful', [
            'token' => $result['token'],
        ]);
    }

    /** POST /v1/auth/logout */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return Api::success(null, 'Logged out successfully');
    }

    /** POST /v1/auth/change-password */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        $this->auth->changePassword($request->user(), $request->only('old_password', 'new_password'));

        return Api::success(null, 'Password changed successfully');
    }
}
