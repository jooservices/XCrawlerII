<?php

namespace Modules\JAV\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;

class FavoriteRepository
{
    public function paginateForUser(int $userId, int $perPage = 30): LengthAwarePaginator
    {
        return Favorite::with(['favoritable'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function isJavLikedByUser(Jav $jav, int $userId): bool
    {
        return $jav->favorites()->where('user_id', $userId)->exists();
    }

    /**
     * @param  Collection<int, int|string>  $javIds
     * @return Collection<int|string, int|string>
     */
    public function likedJavIdsForUserAndJavIds(int $userId, Collection $javIds): Collection
    {
        return Favorite::query()
            ->where('user_id', $userId)
            ->where('favoritable_type', Jav::class)
            ->whereIn('favoritable_id', $javIds)
            ->pluck('favoritable_id')
            ->flip();
    }

    /**
     * @param  Collection<int, int|string>  $actorIds
     * @return Collection<int|string, int|string>
     */
    public function likedActorIdsForUserAndActorIds(int $userId, Collection $actorIds): Collection
    {
        return Favorite::query()
            ->where('user_id', $userId)
            ->where('favoritable_type', Actor::class)
            ->whereIn('favoritable_id', $actorIds)
            ->pluck('favoritable_id')
            ->flip();
    }

    /**
     * @return Collection<int, string>
     */
    public function preferredTagNamesForUser(int $userId): Collection
    {
        return Favorite::query()
            ->where('user_id', $userId)
            ->where('favoritable_type', Tag::class)
            ->join('tags', 'favorites.favoritable_id', '=', 'tags.id')
            ->pluck('tags.name')
            ->unique()
            ->values();
    }
}
