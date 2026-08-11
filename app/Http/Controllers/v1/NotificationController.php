<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationIndexRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    /** 通知一覧取得 */
    public function index(NotificationIndexRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $notifications = $this->notificationService->getNotifications(
            $userId,
            $request->page(),
            $request->size(),
        );

        return response()->json([
            'notifications' => NotificationResource::collection($notifications->items()),
            'unread_count' => $this->notificationService->getUnreadCount($userId),
            'page' => $this->notificationService->getCurrentPage($notifications),
            'size' => $this->notificationService->getPageSize($notifications),
            'total_pages' => $this->notificationService->getTotalPages($notifications),
            'total_elements' => $this->notificationService->getTotalElements($notifications),
        ]);
    }

    /** 未読通知数取得 */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->notificationService->getUnreadCount($request->user()->id),
        ]);
    }

    /** 通知を既読にする */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException('アクセス権限がありません');
        }

        $notification = $this->notificationService->markAsRead($notification);

        return response()->json([
            'notification' => new NotificationResource($notification),
        ]);
    }

    /** すべての通知を既読にする */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
