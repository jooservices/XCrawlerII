<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\JAV\Http\Controllers\Users\Api\NotificationController as ApiNotificationController;
use Modules\JAV\Http\Requests\MarkAllNotificationsReadRequest;
use Modules\JAV\Http\Requests\MarkNotificationReadRequest;
use Modules\JAV\Http\Requests\NotificationsRequest;
use Modules\JAV\Models\UserLikeNotification;
use Modules\JAV\Repositories\DashboardReadRepository;

class NotificationController extends Controller
{
    public function __construct(private readonly DashboardReadRepository $dashboardReadRepository)
    {
    }

    public function index(NotificationsRequest $request): JsonResponse
    {
        return app(ApiNotificationController::class)->index($request);
    }

    public function markNotificationRead(MarkNotificationReadRequest $request, UserLikeNotification $notification): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return app(ApiNotificationController::class)->markNotificationRead($request, $notification);
        }

        $notification->markAsRead();

        return back();
    }

    public function markAllNotificationsRead(MarkAllNotificationsReadRequest $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return app(ApiNotificationController::class)->markAllNotificationsRead($request);
        }

        $this->dashboardReadRepository->markAllNotificationsReadForUser($request->user());

        return back();
    }
}
