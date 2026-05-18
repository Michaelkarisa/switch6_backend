<?php

namespace App\Services;

use App\Models\MatchModel;
use App\Models\User;
use App\Notifications\GenericUserNotification;
use App\Notifications\MatchCreatedNotification;
use App\Notifications\MatchStatusUpdatedNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function __construct(private AuditLogService $audit) {}

    public function listForUser(User $user, int $limit = 20): Collection
    {
        return $user->notifications()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function unreadForUser(User $user, int $limit = 20): Collection
    {
        return $user->unreadNotifications()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(User $user, string $notificationId): ?DatabaseNotification
    {
        $notification = $user->notifications()->whereKey($notificationId)->first();

        if (! $notification) {
            return null;
        }

        $notification->markAsRead();

        $this->audit->log('notification_read', 'notifications', 'Notification marked as read', [
            'notification_id' => $notificationId,
        ], null, null, $user->id);

        return $notification;
    }

    public function markAllAsRead(User $user): int
    {
        $count = $user->unreadNotifications()->count();
        $user->unreadNotifications()->update(['read_at' => now()]);

        $this->audit->log('notifications_read_all', 'notifications', 'All notifications marked as read', [
            'count' => $count,
        ], null, null, $user->id);

        return $count;
    }

    public function delete(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->whereKey($notificationId)->first();

        if (! $notification) {
            return false;
        }

        $notification->delete();

        $this->audit->log('notification_deleted', 'notifications', 'Notification deleted', [
            'notification_id' => $notificationId,
        ], null, null, $user->id);

        return true;
    }

    public function notifyUsers(iterable $users, array $payload): void
    {
        Notification::send($users, new GenericUserNotification($payload));

        $this->audit->log('notification_sent', 'notifications', 'Generic notification sent', [
            'payload' => $payload,
        ]);
    }

    public function notifyMatchCreated(MatchModel $match): void
    {
        $users = $this->matchRecipients($match);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new MatchCreatedNotification($match));

        $this->audit->log('match_created_notification_sent', 'notifications', 'Match created notification sent', [
            'match_id' => $match->id,
            'recipient_count' => $users->count(),
        ], $match);
    }

    public function notifyMatchStatusUpdated(MatchModel $match, ?string $oldStatus, string $newStatus): void
    {
        $users = $this->matchRecipients($match);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new MatchStatusUpdatedNotification($match, $oldStatus, $newStatus));

        $this->audit->log('match_status_notification_sent', 'notifications', 'Match status notification sent', [
            'match_id' => $match->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'recipient_count' => $users->count(),
        ], $match);
    }

    private function matchRecipients(MatchModel $match): Collection
    {
        $query = User::query();

        if ($match->author_id) {
            $query->where('id', $match->author_id);
        }

        return $query->get();
    }
}
