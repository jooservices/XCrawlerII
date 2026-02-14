<?php

namespace Modules\JAV\Repositories;

use Illuminate\Support\Collection;
use Modules\JAV\Models\Watchlist;

class WatchlistRepository
{
    /**
     * @param Collection<int, int|string> $javIds
     * @return Collection<int|string, Watchlist>
     */
    public function keyedByJavIdForUserAndJavIds(int $userId, Collection $javIds): Collection
    {
        return Watchlist::query()
            ->where('user_id', $userId)
            ->whereIn('jav_id', $javIds)
            ->get()
            ->keyBy('jav_id');
    }
}
