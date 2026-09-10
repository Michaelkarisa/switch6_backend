<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ApiResponseService as Api;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    /** GET /v1/notifications */
    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);

        return Api::success(
            $this->notifications->listForUser($request->user(), $limit),
            'Notifications fetched successfully',
            ['unread_count' => $this->notifications->unreadCount($request->user())],
        );
    }

    /** GET /v1/notifications/unread */
    public function unread(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);

        return Api::success(
            $this->notifications->unreadForUser($request->user(), $limit),
            'Unread notifications fetched successfully',
            ['unread_count' => $this->notifications->unreadCount($request->user())],
        );
    }

    /** PATCH /v1/notifications/{notification}/read */
    public function markRead(Request $request, string $notification): JsonResponse
    {
        $item = $this->notifications->markAsRead($request->user(), $notification);

        if (! $item) {
            return Api::notFound('Notification not found');
        }

        return Api::success($item, 'Notification marked as read');
    }

    /** POST /v1/notifications/read-all */
    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->notifications->markAllAsRead($request->user());

        return Api::success(null, 'All notifications marked as read', ['updated' => $count]);
    }

    /** DELETE /v1/notifications/{notification} */
    public function destroy(Request $request, string $notification): JsonResponse
    {
        $deleted = $this->notifications->delete($request->user(), $notification);

        if (! $deleted) {
            return Api::notFound('Notification not found');
        }

        return Api::success(null, 'Notification deleted successfully');
    }

    /** GET /v1/notifications/count */
    public function count(Request $request): JsonResponse
    {
        return Api::success(
            ['count' => $this->notifications->unreadCount($request->user())],
            'Notification count fetched successfully',
        );
    }
}
