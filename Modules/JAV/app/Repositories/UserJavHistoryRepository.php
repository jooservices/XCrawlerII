<?php

namespace Modules\JAV\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\JAV\Models\UserJavHistory;

class UserJavHistoryRepository
{
    /**
     * @return Collection<int, UserJavHistory>
     */
    public function continueWatching(int $userId, int $limit = 8): Collection
    {
        return UserJavHistory::with('jav')
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->get()
            ->unique('jav_id')
            ->take($limit)
            ->values();
    }

    public function paginateForUser(int $userId, int $perPage = 30): LengthAwarePaginator
    {
        return UserJavHistory::with('jav')
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);
    }

    public function recordView(int $userId, int $javId): void
    {
        UserJavHistory::firstOrCreate([
            'user_id' => $userId,
            'jav_id' => $javId,
            'action' => 'view',
        ], [
            'updated_at' => now(),
        ]);
    }

    public function recordDownload(int $userId, int $javId): void
    {
        UserJavHistory::updateOrCreate([
            'user_id' => $userId,
            'jav_id' => $javId,
            'action' => 'download',
        ]);
    }
}
