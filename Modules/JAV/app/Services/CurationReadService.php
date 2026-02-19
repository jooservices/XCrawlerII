<?php

namespace Modules\JAV\Services;

use Modules\Core\Models\CuratedItem;

class CurationReadService
{
    private const FEATURED_TYPE = 'featured';

    private const JAV_ITEM_TYPE = 'jav';

    /**
     * @param  iterable<int, mixed>  $movies
     */
    public function decorateMoviesWithFeaturedState(iterable $movies): void
    {
        $movieList = collect($movies);
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

            $movie->is_featured = $isFeatured;
            $movie->featured_curation_uuid = $curationUuid;
        }
    }

    private function extractMovieId(mixed $movie): ?int
    {
        if (is_array($movie)) {
            $id = $movie['id'] ?? null;

            return is_numeric($id) ? (int) $id : null;
        }

        if (is_object($movie) && isset($movie->id) && is_numeric($movie->id)) {
            return (int) $movie->id;
        }

        return null;
    }
}
