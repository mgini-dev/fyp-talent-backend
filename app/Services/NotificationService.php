<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    public function getNotifications(User $user): Collection
    {
        return Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createNotification(int $userId, string $type, array $data): Notification
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'data'    => $data,
            'is_read' => false,
        ]);

        try {
            event(new \App\Events\NotificationCreated($notification));
        } catch (\Exception $e) {
            \Log::error('Broadcasting notification failed: ' . $e->getMessage());
        }

        return $notification;
    }

    public function markAsRead(User $user, int $id): Notification
    {
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $notification->update(['is_read' => true]);

        return $notification;
    }

    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
