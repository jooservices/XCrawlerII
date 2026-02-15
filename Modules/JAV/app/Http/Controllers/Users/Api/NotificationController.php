<?php

namespace Modules\JAV\Http\Controllers\Users\Api;

use Illuminate\Http\JsonResponse;
use Modules\JAV\Http\Controllers\Api\ApiController;
use Modules\JAV\Http\Requests\MarkAllNotificationsReadRequest;
use Modules\JAV\Http\Requests\MarkNotificationReadRequest;
use Modules\JAV\Http\Requests\NotificationsRequest;
use Modules\JAV\Models\UserLikeNotification;
use Modules\JAV\Repositories\DashboardReadRepository;

class NotificationController extends ApiController
{
    public function __construct(private readonly DashboardReadRepository $dashboardReadRepository) {}

    public function index(NotificationsRequest $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $this->dashboardReadRepository->unreadNotificationsForUser($user, 20);

        return $this->result([
            'count' => $notifications->count(),
            'items' => $notifications,
        ]);
    }

    public function markNotificationRead(MarkNotificationReadRequest $request, UserLikeNotification $notification): JsonResponse
    {
        $notification->markAsRead();

        return $this->result();
    }

    public function markAllNotificationsRead(MarkAllNotificationsReadRequest $request): JsonResponse
    {
        $this->dashboardReadRepository->markAllNotificationsReadForUser($request->user());

        return $this->result();
    }
}
