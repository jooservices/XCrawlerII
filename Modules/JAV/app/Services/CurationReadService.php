<?php

namespace Modules\JAV\Services;

use Modules\Core\Models\CuratedItem;

class CurationReadService
{
    private const FEATURED_TYPE = 'featured';

    private const JAV_ITEM_TYPE = 'jav';

    private const ACTOR_ITEM_TYPE = 'actor';

    private const TAG_ITEM_TYPE = 'tag';

    /**
     * @param  iterable<int, mixed>  $movies
     */
    public function decorateMoviesWithFeaturedState(iterable $movies): void
    {
        $movieList = $movies instanceof \Illuminate\Support\Collection ? $movies : collect($movies);
        if ($movieList->isEmpty()) {
            return;
        }

        $movieIds = $movieList
            ->map(fn ($movie): ?int => $this->extractMovieId($movie))
            ->filter(fn ($id): bool => $id !== null)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($movieIds->isEmpty()) {
            return;
        }

        $featuredMap = CuratedItem::query()
            ->where('item_type', self::JAV_ITEM_TYPE)
            ->where('curation_type', self::FEATURED_TYPE)
            ->whereIn('item_id', $movieIds)
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest()
            ->get(['item_id', 'uuid'])
            ->keyBy('item_id');

        foreach ($movieList as $movie) {
            $movieId = $this->extractMovieId($movie);
            if ($movieId === null) {
                continue;
            }

            $curation = $featuredMap->get($movieId);
            $isFeatured = $curation !== null;
            $curationUuid = $curation?->uuid;

            if (is_array($movie)) {
                $movie['is_featured'] = $isFeatured;
                $movie['featured_curation_uuid'] = $curationUuid;

                continue;
            }

            $movie->setAttribute('is_featured', $isFeatured);
            $movie->setAttribute('featured_curation_uuid', $curationUuid);
        }
    }

    /**
     * @param  iterable<int, mixed>  $actors
     */
    public function decorateActorsWithFeaturedState(iterable $actors): void
    {
        $actorList = $actors instanceof \Illuminate\Support\Collection ? $actors : collect($actors);
        if ($actorList->isEmpty()) {
            return;
        }

        $actorIds = $actorList
            ->map(fn ($actor): ?int => $this->extractItemId($actor))
            ->filter(fn ($id): bool => $id !== null)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($actorIds->isEmpty()) {
            return;
        }

        $featuredMap = CuratedItem::query()
            ->where('item_type', self::ACTOR_ITEM_TYPE)
            ->where('curation_type', self::FEATURED_TYPE)
            ->whereIn('item_id', $actorIds)
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest()
            ->get(['item_id', 'uuid'])
            ->keyBy('item_id');

        foreach ($actorList as $key => $actor) {
            $actorId = $this->extractItemId($actor);
            if ($actorId === null) {
                continue;
            }

            $curation = $featuredMap->get($actorId);
            $isFeatured = $curation !== null;
            $curationUuid = $curation?->uuid;

            if (is_array($actor)) {
                $actor['is_featured'] = $isFeatured;
                $actor['featured_curation_uuid'] = $curationUuid;
                $actorList->offsetSet($key, $actor);

                continue;
            }

            $actor->setAttribute('is_featured', $isFeatured);
            $actor->setAttribute('featured_curation_uuid', $curationUuid);
        }
    }

    /**
     * @param  iterable<int, mixed>  $tags
     */
    public function decorateTagsWithFeaturedState(iterable $tags): void
    {
        $tagList = $tags instanceof \Illuminate\Support\Collection ? $tags : collect($tags);
        if ($tagList->isEmpty()) {
            return;
        }

        $tagIds = $tagList
            ->map(fn ($tag): ?int => $this->extractItemId($tag))
            ->filter(fn ($id): bool => $id !== null)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($tagIds->isEmpty()) {
            return;
        }

        $featuredMap = CuratedItem::query()
            ->where('item_type', self::TAG_ITEM_TYPE)
            ->where('curation_type', self::FEATURED_TYPE)
            ->whereIn('item_id', $tagIds)
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest()
            ->get(['item_id', 'uuid'])
            ->keyBy('item_id');

        foreach ($tagList as $key => $tag) {
            $tagId = $this->extractItemId($tag);
            if ($tagId === null) {
                continue;
            }

            $curation = $featuredMap->get($tagId);
            $isFeatured = $curation !== null;
            $curationUuid = $curation?->uuid;

            if (is_array($tag)) {
                $tag['is_featured'] = $isFeatured;
                $tag['featured_curation_uuid'] = $curationUuid;
                $tagList->offsetSet($key, $tag);

                continue;
            }

            $tag->setAttribute('is_featured', $isFeatured);
            $tag->setAttribute('featured_curation_uuid', $curationUuid);
        }
    }

    private function extractItemId(mixed $item): ?int
    {
        if (is_array($item)) {
            $id = $item['id'] ?? null;

            return is_numeric($id) ? (int) $id : null;
        }

        if (is_object($item) && isset($item->id) && is_numeric($item->id)) {
            return (int) $item->id;
        }

        return null;
    }

    private function extractMovieId(mixed $movie): ?int
    {
        return $this->extractItemId($movie);
    }
}
