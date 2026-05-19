<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        $notifications = $this->notificationService->getNotifications($request->user());

        return response()->json([
            'status' => 'success',
            'data'   => $notifications,
        ]);
    }

    public function markAsRead(Request $request, $id): JsonResponse
    {
        $notification = $this->notificationService->markAsRead($request->user(), (int) $id);

        return response()->json([
            'status' => 'success',
            'data'   => $notification,
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'status'  => 'success',
            'message' => 'All notifications marked as read',
        ]);
    }
}
