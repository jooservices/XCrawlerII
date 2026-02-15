<?php

namespace Modules\JAV\Repositories;

use Illuminate\Support\Collection;
use Modules\JAV\Models\Rating;

class RatingRepository
{
    /**
     * @param  Collection<int, int|string>  $javIds
     * @return Collection<int|string, Rating>
     */
    public function keyedByJavIdForUserAndJavIds(int $userId, Collection $javIds): Collection
    {
        return Rating::query()
            ->where('user_id', $userId)
            ->whereIn('jav_id', $javIds)
            ->get()
            ->keyBy('jav_id');
    }
}
