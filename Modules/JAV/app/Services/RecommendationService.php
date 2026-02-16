<?php

namespace Modules\JAV\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Mongo\RecommendationSnapshot;
use Modules\JAV\Models\UserJavHistory;

class RecommendationService
{
    private const SNAPSHOT_STALE_HOURS = 12;

    public function getRecommendationsWithReasons($user, $limit = 20)
    {
        $snapshotRecommendations = $this->loadRecommendationsFromSnapshot((int) $user->id, (int) $limit);
        if ($snapshotRecommendations !== null && $snapshotRecommendations->isNotEmpty()) {
            return $snapshotRecommendations;
        }

        $movies = $this->getRecommendations($user, $limit);
        $recommendations = $this->buildRecommendationsWithReasons($user, $movies);
        $this->storeSnapshot((int) $user->id, $recommendations);

        return $recommendations;
    }

    public function getRecommendations($user, $limit = 20)
    {
        // 1. Get user's liked actors and tags
        $likedActors = $user->favorites()->where('favoritable_type', \Modules\JAV\Models\Actor::class)->pluck('favoritable_id');
        $likedTags = $user->favorites()->where('favoritable_type', \Modules\JAV\Models\Tag::class)->pluck('favoritable_id');

        // Also get actors/tags from liked movies
        $likedMovies = $user->favorites()->where('favoritable_type', \Modules\JAV\Models\Jav::class)->with('favoritable.actors', 'favoritable.tags')->get();
        foreach ($likedMovies as $favorite) {
            $likedActors = $likedActors->merge($favorite->favoritable->actors->pluck('id'));
            $likedTags = $likedTags->merge($favorite->favoritable->tags->pluck('id'));
        }

        $likedActors = $likedActors->unique();
        $likedTags = $likedTags->unique();

        if ($likedActors->isEmpty() && $likedTags->isEmpty()) {
            return collect(); // No recommendations if no likes
        }

        // 2. Find movies with these actors or tags
        // We want to score them: +1 for each matching actor, +1 for each matching tag
        // Simple implementation: Use database queries

        // This can be heavy, so limit candidates or use specific strategy.
        // Strategy: Get candidates that have at least one of the actors OR tags.
        // Order by match count (desc), then by popularity (views).

        // Construct query
        $query = Jav::query()->with(['actors', 'tags']);

        $query->where(function ($q) use ($likedActors, $likedTags) {
            if ($likedActors->isNotEmpty()) {
                $q->orWhereHas('actors', function ($q) use ($likedActors) {
                    $q->whereIn('actors.id', $likedActors);
                });
            }
            if ($likedTags->isNotEmpty()) {
                $q->orWhereHas('tags', function ($q) use ($likedTags) {
                    $q->whereIn('tags.id', $likedTags);
                });
            }
        });

        // Exclude already viewed or liked movies
        $viewedIds = UserJavHistory::where('user_id', $user->id)->pluck('jav_id');
        $likedIds = $user->favorites()->where('favoritable_type', \Modules\JAV\Models\Jav::class)->pluck('favoritable_id');
        $excludeIds = $viewedIds->merge($likedIds)->unique();

        $query->whereNotIn('id', $excludeIds);

        // Sorting: Ideally by relevance.
        // Implementing "Sort by relevance" in pure Eloquent cleanly is hard without raw queries.
        // For now, let's sort by popularity (views) as a proxy for quality among relevant items.
        $query->orderBy('views', 'desc')->orderBy('downloads', 'desc');

        return $query->take($limit)->get();
    }

    public function syncSnapshotForUserId(int $userId, int $limit = 30): bool
    {
        $user = User::query()->find($userId);
        if (! $user) {
            return false;
        }

        $movies = $this->getRecommendations($user, $limit);
        $recommendations = $this->buildRecommendationsWithReasons($user, $movies);
        $this->storeSnapshot($user->id, $recommendations);

        return true;
    }

    public function syncSnapshotsForUsersByJav(Jav $jav, int $limit = 30): int
    {
        $jav->loadMissing(['actors:id', 'tags:id']);

        $actorIds = $jav->actors->pluck('id')->filter()->values();
        $tagIds = $jav->tags->pluck('id')->filter()->values();

        if ($actorIds->isEmpty() && $tagIds->isEmpty()) {
            return 0;
        }

        $query = DB::table('favorites')
            ->select('user_id')
            ->distinct();

        $query->where(function ($favoriteQuery) use ($actorIds, $tagIds): void {
            if ($actorIds->isNotEmpty()) {
                $favoriteQuery->orWhere(function ($q) use ($actorIds): void {
                    $q->where('favoritable_type', \Modules\JAV\Models\Actor::class)
                        ->whereIn('favoritable_id', $actorIds);
                });
            }

            if ($tagIds->isNotEmpty()) {
                $favoriteQuery->orWhere(function ($q) use ($tagIds): void {
                    $q->where('favoritable_type', \Modules\JAV\Models\Tag::class)
                        ->whereIn('favoritable_id', $tagIds);
                });
            }
        });

        $userIds = $query->pluck('user_id')->map(static fn ($id): int => (int) $id)->all();
        $synced = 0;
        foreach ($userIds as $userId) {
            if ($this->syncSnapshotForUserId($userId, $limit)) {
                $synced++;
            }
        }

        return $synced;
    }

    private function buildRecommendationsWithReasons(User $user, Collection $movies): Collection
    {
        $likedActorNames = $user->favorites()
            ->where('favoritable_type', \Modules\JAV\Models\Actor::class)
            ->join('actors', 'favorites.favoritable_id', '=', 'actors.id')
            ->pluck('actors.name')
            ->unique()
            ->values();

        $likedTagNames = $user->favorites()
            ->where('favoritable_type', \Modules\JAV\Models\Tag::class)
            ->join('tags', 'favorites.favoritable_id', '=', 'tags.id')
            ->pluck('tags.name')
            ->unique()
            ->values();

        return $movies->map(function (Jav $movie) use ($likedActorNames, $likedTagNames): array {
            $movieActorNames = $movie->actors->pluck('name');
            $movieTagNames = $movie->tags->pluck('name');

            $actorMatches = $movieActorNames
                ->intersect($likedActorNames)
                ->take(2)
                ->values()
                ->all();

            $tagMatches = $movieTagNames
                ->intersect($likedTagNames)
                ->take(2)
                ->values()
                ->all();

            return [
                'movie' => $movie,
                'reasons' => [
                    'actors' => $actorMatches,
                    'tags' => $tagMatches,
                ],
            ];
        })->values();
    }

    private function loadRecommendationsFromSnapshot(int $userId, int $limit): ?Collection
    {
        try {
            $snapshot = RecommendationSnapshot::query()
                ->where('user_id', $userId)
                ->first();

            if (! $snapshot || ! is_array($snapshot->payload) || ! $snapshot->generated_at) {
                return null;
            }

            if ($snapshot->generated_at->lt(now()->subHours(self::SNAPSHOT_STALE_HOURS))) {
                return null;
            }

            $items = collect($snapshot->payload['items'] ?? [])
                ->filter(static fn ($row): bool => is_array($row) && isset($row['jav_id']))
                ->take($limit)
                ->values();

            if ($items->isEmpty()) {
                return collect();
            }

            $javIds = $items->pluck('jav_id')->map(static fn ($id): int => (int) $id)->all();
            $movies = Jav::query()
                ->with(['actors', 'tags'])
                ->whereIn('id', $javIds)
                ->get()
                ->keyBy('id');

            return $items->map(function (array $row) use ($movies): ?array {
                $movie = $movies->get((int) $row['jav_id']);
                if (! $movie) {
                    return null;
                }

                $reasons = is_array($row['reasons'] ?? null) ? $row['reasons'] : [];

                return [
                    'movie' => $movie,
                    'reasons' => [
                        'actors' => array_values(array_filter((array) ($reasons['actors'] ?? []))),
                        'tags' => array_values(array_filter((array) ($reasons['tags'] ?? []))),
                    ],
                ];
            })->filter()->values();
        } catch (\Throwable) {
            return null;
        }
    }

    private function storeSnapshot(int $userId, Collection $recommendations): void
    {
        try {
            $items = $recommendations->map(static function (array $entry): array {
                /** @var \Modules\JAV\Models\Jav $movie */
                $movie = $entry['movie'];

                return [
                    'jav_id' => (int) $movie->id,
                    'reasons' => [
                        'actors' => array_values(array_filter((array) ($entry['reasons']['actors'] ?? []))),
                        'tags' => array_values(array_filter((array) ($entry['reasons']['tags'] ?? []))),
                    ],
                ];
            })->values()->all();

            RecommendationSnapshot::query()->updateOrCreate(
                ['user_id' => $userId],
                [
                    'generated_at' => now(),
                    'payload' => ['items' => $items],
                ]
            );
        } catch (\Throwable) {
            // Keep recommendation rendering non-blocking when Mongo is unavailable.
        }
    }
}
