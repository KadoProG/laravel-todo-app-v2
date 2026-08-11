<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /** 通知を作成する */
    public function createNotification(
        int $userId,
        NotificationType $type,
        string $title,
        ?string $message = null,
        ?int $relatedTaskId = null,
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_task_id' => $relatedTaskId,
            'is_read' => false,
        ]);
    }

    /** 通知一覧を取得する */
    public function getNotifications(int $userId, int $page, int $size): LengthAwarePaginator
    {
        return Notification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate(perPage: $size, page: $page + 1);
    }

    /** 未読通知数を取得する */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)->unread()->count();
    }

    /** 現在のページ番号を取得する（Kotlin 側の Page に合わせ 0 始まり） */
    public function getCurrentPage(LengthAwarePaginator $paginator): int
    {
        return (int) $paginator->currentPage() - 1;
    }

    /** 1ページあたりの件数を取得する */
    public function getPageSize(LengthAwarePaginator $paginator): int
    {
        return (int) $paginator->perPage();
    }

    /** 総ページ数を取得する */
    public function getTotalPages(LengthAwarePaginator $paginator): int
    {
        return (int) $paginator->lastPage();
    }

    /** 総件数を取得する */
    public function getTotalElements(LengthAwarePaginator $paginator): int
    {
        return (int) $paginator->total();
    }

    /** 通知を既読にする */
    public function markAsRead(Notification $notification): Notification
    {
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $notification->refresh();
    }

    /** すべての通知を既読にする */
    public function markAllAsRead(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            Notification::where('user_id', $userId)
                ->unread()
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        });
    }
}
