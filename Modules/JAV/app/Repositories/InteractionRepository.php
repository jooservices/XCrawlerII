<?php

namespace Modules\JAV\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;

class InteractionRepository
{
    public function paginateFavoritesForUser(int $userId, int $perPage = 30): LengthAwarePaginator
    {
        return Interaction::with(['item'])
            ->where('user_id', $userId)
            ->where('action', Interaction::ACTION_FAVORITE)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function isJavLikedByUser(Jav $jav, int $userId): bool
    {
        return Interaction::query()
            ->where('user_id', $userId)
            ->where('item_type', Interaction::morphTypeFor(Jav::class))
            ->where('item_id', $jav->id)
            ->where('action', Interaction::ACTION_FAVORITE)
            ->exists();
    }

    /**
     * @param  Collection<int, int|string>  $javIds
     * @return Collection<int|string, int|string>
     */
    public function likedJavIdsForUserAndJavIds(int $userId, Collection $javIds): Collection
    {
        return Interaction::query()
            ->where('user_id', $userId)
            ->where('item_type', Interaction::morphTypeFor(Jav::class))
            ->where('action', Interaction::ACTION_FAVORITE)
            ->whereIn('item_id', $javIds)
            ->pluck('item_id')
            ->flip();
    }

    /**
     * @param  Collection<int, int|string>  $actorIds
     * @return Collection<int|string, int|string>
     */
    public function likedActorIdsForUserAndActorIds(int $userId, Collection $actorIds): Collection
    {
        return Interaction::query()
            ->where('user_id', $userId)
            ->where('item_type', Interaction::morphTypeFor(Actor::class))
            ->where('action', Interaction::ACTION_FAVORITE)
            ->whereIn('item_id', $actorIds)
            ->pluck('item_id')
            ->flip();
    }

    /**
     * @return Collection<int, string>
     */
    public function preferredTagNamesForUser(int $userId): Collection
    {
        return Interaction::query()
            ->where('user_id', $userId)
            ->where('item_type', Interaction::morphTypeFor(Tag::class))
            ->where('action', Interaction::ACTION_FAVORITE)
            ->join('tags', 'user_interactions.item_id', '=', 'tags.id')
            ->pluck('tags.name')
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, int|string>  $javIds
     * @return Collection<int|string, Interaction>
     */
    public function keyedRatingsForUserAndJavIds(int $userId, Collection $javIds): Collection
    {
        return Interaction::query()
            ->where('user_id', $userId)
            ->where('item_type', Interaction::morphTypeFor(Jav::class))
            ->where('action', Interaction::ACTION_RATING)
            ->whereIn('item_id', $javIds)
            ->get()
            ->keyBy('item_id');
    }

    public function findRatingForUserAndJav(int $userId, int $javId): ?Interaction
    {
        return Interaction::query()
            ->where('user_id', $userId)
            ->where('item_type', Interaction::morphTypeFor(Jav::class))
            ->where('item_id', $javId)
            ->where('action', Interaction::ACTION_RATING)
            ->first();
    }
}
