<?php

namespace Modules\JAV\Http\Controllers\Users\Api;

use Illuminate\Http\JsonResponse;
use Modules\JAV\Http\Controllers\Api\ApiController;
use Modules\JAV\Http\Requests\StoreRatingRequest;
use Modules\JAV\Http\Requests\UpdateRatingRequest;
use Modules\JAV\Models\Rating;

class RatingController extends ApiController
{
    public function store(StoreRatingRequest $request): JsonResponse
    {
        $existingRating = Rating::query()
            ->where('user_id', $request->user()->id)
            ->where('jav_id', $request->input('jav_id'))
            ->first();

        if ($existingRating) {
            return $this->error(
                'You have already rated this movie. Please update your existing rating instead.',
                422
            );
        }

        $rating = Rating::create([
            'user_id' => $request->user()->id,
            'jav_id' => $request->input('jav_id'),
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
        if (! auth()->check() || $rating->user_id !== auth()->id()) {
            return $this->error('Unauthorized.', 403);
        }

        $rating->delete();

        return $this->result([
            'message' => 'Rating deleted successfully!',
        ]);
    }

    public function check(int $javId): JsonResponse
    {
        if (! auth()->check()) {
            return $this->result(['has_rated' => false]);
        }

        $rating = Rating::query()
            ->where('user_id', auth()->id())
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
}
