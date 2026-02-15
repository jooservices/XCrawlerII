<?php

namespace Modules\JAV\Repositories;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Services\SearchService;

class DashboardReadRepository
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly JavRepository $javRepository,
        private readonly ActorRepository $actorRepository,
        private readonly TagRepository $tagRepository,
        private readonly FavoriteRepository $favoriteRepository,
        private readonly UserJavHistoryRepository $historyRepository,
        private readonly WatchlistRepository $watchlistRepository,
        private readonly RatingRepository $ratingRepository,
        private readonly UserLikeNotificationRepository $notificationRepository,
    ) {
    }

    public function searchWithPreset(
        string $query,
        array $filters,
        int $perPage,
        ?string $sort,
        string $direction,
        string $preset,
        ?Authenticatable $user = null
    ): LengthAwarePaginator {
        if ($preset === 'default') {
            return $this->searchService->searchJav($query, $filters, $perPage, $sort, $direction);
        }

        $sortField = in_array((string) $sort, ['created_at', 'updated_at', 'views', 'downloads'], true)
            ? (string) $sort
            : 'created_at';
        $sortDirection = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        $builder = $this->javRepository->queryWithRelations();
        $this->searchService->applyDatabaseFilters($builder, $query, $filters);

        if ($preset === 'weekly_downloads') {
            $builder->where('created_at', '>=', now()->subWeek());
            $sortField = 'downloads';
            $sortDirection = 'desc';
        }

        if ($preset === 'preferred_tags' && $user !== null) {
            $preferredTagNames = $this->favoriteRepository->preferredTagNamesForUser((int) $user->getAuthIdentifier());

            if ($preferredTagNames->isNotEmpty()) {
                $builder->whereHas('tags', function ($q) use ($preferredTagNames): void {
                    $q->whereIn('name', $preferredTagNames);
                });
            } else {
                $builder->whereRaw('1 = 0');
            }
        }

        return $builder
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function decorateItemsForUser(LengthAwarePaginator $items, ?Authenticatable $user): void
    {
        if ($user === null) {
            return;
        }

        /** @var Collection<int, mixed> $itemCollection */
        $itemCollection = $items->getCollection();
        $ids = $itemCollection
            ->map(static fn($item) => $item->id ?? null)
            ->filter(static fn($id) => $id !== null)
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $userId = (int) $user->getAuthIdentifier();
        $likedIds = $this->favoriteRepository->likedJavIdsForUserAndJavIds($userId, $ids);
        $watchlist = $this->watchlistRepository->keyedByJavIdForUserAndJavIds($userId, $ids);
        $ratings = $this->ratingRepository->keyedByJavIdForUserAndJavIds($userId, $ids);

        $itemCollection->transform(function ($item) use ($likedIds, $watchlist, $ratings) {
            $itemId = $item->id ?? null;
            $item->is_liked = $itemId !== null && $likedIds->has($itemId);
            $item->watchlist_id = $itemId !== null ? optional($watchlist->get($itemId))->id : null;
            $item->in_watchlist = $item->watchlist_id !== null;
            $item->user_rating = $itemId !== null ? optional($ratings->get($itemId))->rating : null;
            $item->user_rating_id = $itemId !== null ? optional($ratings->get($itemId))->id : null;

            return $item;
        });

        $items->setCollection($itemCollection);
    }

    /**
     * @return Collection<int, mixed>
     */
    public function continueWatching(int $userId, int $limit = 8): Collection
    {
        return $this->historyRepository->continueWatching($userId, $limit);
    }

    public function actorMovies(Actor $actor, int $perPage = 30): LengthAwarePaginator
    {
        return $this->actorRepository->actorMovies($actor, $perPage);
    }

    public function searchActors(
        string $query,
        array $filters = [],
        int $perPage = 60,
        ?string $sort = null,
        string $direction = 'desc'
    ): LengthAwarePaginator
    {
        return $this->searchService->searchActors($query, $filters, $perPage, $sort, $direction);
    }

    public function searchTags(string $query): LengthAwarePaginator
    {
        return $this->searchService->searchTags($query);
    }

    public function loadJavRelations(Jav $jav): Jav
    {
        return $this->javRepository->loadRelations($jav);
    }

    public function isJavLikedByUser(Jav $jav, int $userId): bool
    {
        return $this->favoriteRepository->isJavLikedByUser($jav, $userId);
    }

    public function historyForUser(int $userId, int $perPage = 30): LengthAwarePaginator
    {
        return $this->historyRepository->paginateForUser($userId, $perPage);
    }

    public function favoritesForUser(int $userId, int $perPage = 30): LengthAwarePaginator
    {
        return $this->favoriteRepository->paginateForUser($userId, $perPage);
    }

    /**
     * @return Collection<int, mixed>
     */
    public function unreadNotificationsForUser(Authenticatable $user, int $limit = 20): Collection
    {
        return $this->notificationRepository->unreadForUser($user, $limit);
    }

    public function markAllNotificationsReadForUser(Authenticatable $user): int
    {
        return $this->notificationRepository->markAllReadForUser($user);
    }

    /**
     * @return array<int, string>
     */
    public function actorSuggestions(): array
    {
        return Cache::remember('jav:search:actor-suggestions', now()->addMinutes(10), function (): array {
            return $this->actorRepository->suggestions(500);
        });
    }

    /**
     * @return array<int, string>
     */
    public function tagSuggestions(): array
    {
        return Cache::remember('jav:search:tag-suggestions', now()->addMinutes(10), function (): array {
            return $this->tagRepository->suggestions(700);
        });
    }

    /**
     * @param array<string, string> $bioKeys
     * @return array<string, array<int, string>>
     */
    public function bioValueSuggestions(array $bioKeys): array
    {
        $cacheKey = 'jav:search:bio-value-suggestions:v2';

        /** @var array<string, array<int, string>> $suggestions */
        $suggestions = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($bioKeys): array {
            $results = [];
            foreach (array_keys($bioKeys) as $bioKey) {
                $results[$bioKey] = $this->bioValuesByKey($bioKey);
            }

            return $results;
        });

        return $suggestions;
    }

    /**
     * @return array<int, string>
     */
    private function bioValuesByKey(string $bioKey): array
    {
        $attributeValues = DB::table('actor_profile_attributes')
            ->where('kind', $bioKey)
            ->selectRaw("COALESCE(NULLIF(value_string, ''), NULLIF(value_label, ''), NULLIF(raw_value, ''), CAST(value_number AS CHAR), CAST(value_date AS CHAR)) as value")
            ->whereRaw("COALESCE(NULLIF(value_string, ''), NULLIF(value_label, ''), NULLIF(raw_value, ''), CAST(value_number AS CHAR), CAST(value_date AS CHAR)) IS NOT NULL")
            ->distinct()
            ->limit(200)
            ->pluck('value')
            ->map(static fn(mixed $value): string => trim((string) $value))
            ->filter(static fn(string $value): bool => $value !== '')
            ->values()
            ->all();

        $legacyColumn = $this->legacyActorColumnForBioKey($bioKey);
        if ($legacyColumn === null) {
            return collect($attributeValues)->unique()->values()->all();
        }

        $legacyValues = $this->actorRepository->uniqueColumnValues($legacyColumn, 200);

        return collect(array_merge($attributeValues, $legacyValues))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function legacyActorColumnForBioKey(string $bioKey): ?string
    {
        return match ($bioKey) {
            'birth_date' => 'xcity_birth_date',
            'blood_type' => 'xcity_blood_type',
            'city_of_birth' => 'xcity_city_of_birth',
            'height' => 'xcity_height',
            'size' => 'xcity_size',
            'hobby' => 'xcity_hobby',
            'special_skill' => 'xcity_special_skill',
            'other' => 'xcity_other',
            default => null,
        };
    }
}
