<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiResponseService as Api;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function broadcast(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'body'       => ['required', 'string'],
            'role'       => ['nullable', 'string'],
            'status'     => ['nullable', 'in:active,suspended'],
            'user_ids'   => ['nullable', 'array'],
            'user_ids.*' => ['uuid', 'exists:users,id'],
        ]);

        $query = User::query();

        if (! empty($data['user_ids'])) {
            $query->whereIn('id', $data['user_ids']);
        } else {
            $query->when($data['role']   ?? null, fn ($q, $r) => $q->where('role', $r));
            $query->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s));
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            return Api::notFound('No users matched the given filters');
        }

        $this->notifications->notifyUsers($users, [
            'title' => $data['title'],
            'body'  => $data['body'],
        ]);

        return Api::success(null, 'Notification broadcast successfully', [
            'recipient_count' => $users->count(),
        ]);
    }
}
