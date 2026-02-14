<?php

namespace Modules\JAV\Repositories;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class UserLikeNotificationRepository
{
    /**
     * @return Collection<int, mixed>
     */
    public function unreadForUser(Authenticatable $user, int $limit = 20): Collection
    {
        return $user->javNotifications()
            ->with('jav:id,uuid,code,title')
            ->unread()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function markAllReadForUser(Authenticatable $user): int
    {
        return $user->javNotifications()
            ->unread()
            ->update(['read_at' => now()]);
    }
}
