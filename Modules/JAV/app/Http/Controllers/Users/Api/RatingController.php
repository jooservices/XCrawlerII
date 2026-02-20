<?php

namespace Modules\JAV\Http\Controllers\Users\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\JAV\Http\Controllers\Api\ApiController;
use Modules\JAV\Http\Requests\StoreRatingRequest;
use Modules\JAV\Http\Requests\UpdateRatingRequest;
use Modules\JAV\Models\Rating;

class RatingController extends ApiController
{
    public function store(StoreRatingRequest $request): JsonResponse
    {
        $javId = $request->input('jav_id');
        $tagId = $request->input('tag_id');

        $existingRating = Rating::query()
            ->where('user_id', $request->user()->id)
            ->when($javId, fn ($query) => $query->where('jav_id', $javId))
            ->when($tagId, fn ($query) => $query->where('tag_id', $tagId))
            ->first();

        $targetLabel = $tagId ? 'tag' : 'movie';

        if ($existingRating) {
            return $this->error(
                "You have already rated this {$targetLabel}. Please update your existing rating instead.",
                422
            );
        }

        $rating = Rating::create([
            'user_id' => $request->user()->id,
            'jav_id' => $javId,
            'tag_id' => $tagId,
            'rating' => $request->input('rating'),
            'review' => $request->input('review'),
        ]);

        return $this->created($rating->load('user'), 'Rating submitted successfully!');
    }

    public function update(UpdateRatingRequest $request, Rating $rating): JsonResponse
    {
        $rating->update([
            'rating' => $request->input('rating'),
            'review' => $request->input('review'),
        ]);

        return $this->result([
            'message' => 'Rating updated successfully!',
            'data' => $rating->fresh()->load('user'),
        ]);
    }

    public function destroy(Rating $rating): JsonResponse
    {
        if (! Auth::check() || $rating->user_id !== Auth::id()) {
            return $this->error('Unauthorized.', 403);
        }

        $rating->delete();

        return $this->result([
            'message' => 'Rating deleted successfully!',
        ]);
    }

    public function check(int $javId): JsonResponse
    {
        if (! Auth::check()) {
            return $this->result(['has_rated' => false]);
        }

        $rating = Rating::query()
            ->where('user_id', Auth::id())
            ->where('jav_id', $javId)
            ->first();

        if (! $rating) {
            return $this->result(['has_rated' => false]);
        }

        return $this->result([
            'has_rated' => true,
            'rating' => $rating->rating,
            'review' => $rating->review,
            'id' => $rating->id,
        ]);
    }

    public function checkTag(int $tagId): JsonResponse
    {
        if (! Auth::check()) {
            return $this->result(['has_rated' => false]);
        }

        $rating = Rating::query()
            ->where('user_id', Auth::id())
            ->where('tag_id', $tagId)
            ->first();

        if (! $rating) {
            return $this->result(['has_rated' => false]);
        }

        return $this->result([
            'has_rated' => true,
            'rating' => $rating->rating,
            'review' => $rating->review,
            'id' => $rating->id,
        ]);
    }
}
