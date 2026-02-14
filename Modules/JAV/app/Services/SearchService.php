<?php

namespace Modules\JAV\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;

class SearchService
{
    public function searchJav(string $query = '', array $filters = [], int $perPage = 30, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        $search = Jav::search($query);

        if ($sort && in_array($sort, ['created_at', 'views', 'downloads'])) {
            $search->orderBy($sort, $direction);
        }

        $search->query(fn($q) => $q->with(['actors', 'tags']));

        if (!empty($filters['actor'])) {
            $search->where('actors', $filters['actor']);
        }

        if (!empty($filters['tag'])) {
            $search->where('tags', $filters['tag']);
        }

        return $search->paginate($perPage);
    }

    public function searchActors(string $query = '', int $perPage = 60): LengthAwarePaginator
    {
        return Actor::search($query)->query(fn($q) => $q->withCount('javs'))->paginate($perPage);
    }

    public function searchTags(string $query = '', int $perPage = 60): LengthAwarePaginator
    {
        return Tag::search($query)->query(fn($q) => $q->withCount('javs'))->paginate($perPage);
    }

    /**
     * Get related movies by actors
     */
    public function getRelatedByActors(Jav $jav, int $limit = 10): \Illuminate\Support\Collection
    {
        $actorNames = $jav->actors->pluck('name')->toArray();

        if (empty($actorNames)) {
            return collect();
        }

        // Search for movies with any of the same actors, excluding the current movie
        $results = Jav::search('*')
            ->query(fn($q) => $q->with(['actors', 'tags']))
            ->take($limit + 10) // Get extra to filter out current movie
            ->get()
            ->filter(function ($item) use ($jav, $actorNames) {
                if ($item->id === $jav->id) {
                    return false;
                }
                $itemActors = is_array($item->actors)
                    ? collect($item->actors)->pluck('name')->toArray()
                    : $item->actors->pluck('name')->toArray();

                return !empty(array_intersect($itemActors, $actorNames));
            })
            ->take($limit);

        return $results;
    }

    /**
     * Get related movies by tags
     */
    public function getRelatedByTags(Jav $jav, int $limit = 10): \Illuminate\Support\Collection
    {
        $tagNames = $jav->tags->pluck('name')->toArray();

        if (empty($tagNames)) {
            return collect();
        }

        // Search for movies with any of the same tags, excluding the current movie
        $results = Jav::search('*')
            ->query(fn($q) => $q->with(['actors', 'tags']))
            ->take($limit + 10) // Get extra to filter out current movie
            ->get()
            ->filter(function ($item) use ($jav, $tagNames) {
                if ($item->id === $jav->id) {
                    return false;
                }
                $itemTags = is_array($item->tags)
                    ? collect($item->tags)->pluck('name')->toArray()
                    : $item->tags->pluck('name')->toArray();

                return !empty(array_intersect($itemTags, $tagNames));
            })
            ->take($limit);

        return $results;
    }
}
