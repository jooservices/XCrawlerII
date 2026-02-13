<?php

namespace Modules\JAV\Services;

use Illuminate\Support\Facades\DB;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserJavHistory;

class RecommendationService
{
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
        $query = Jav::query();

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
}
