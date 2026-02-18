<?php

namespace Modules\JAV\Http\Controllers\Users\Api;

use Illuminate\Http\JsonResponse;
use Modules\JAV\Http\Controllers\Api\ApiController;
use Modules\JAV\Http\Requests\StoreRatingRequest;
use Modules\JAV\Http\Requests\UpdateRatingRequest;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;

class RatingController extends ApiController
{
    public function store(StoreRatingRequest $request): JsonResponse
    {
        $existingRating = Interaction::query()
            ->where('user_id', $request->user()->id)
            ->where('item_type', Interaction::morphTypeFor(Jav::class))
            ->where('item_id', $request->input('jav_id'))
            ->where('action', Interaction::ACTION_RATING)
            ->first();

        if ($existingRating) {
            return $this->error(
                'You have already rated this movie. Please update your existing rating instead.',
                422
            );
        }

        $rating = Interaction::create([
            'user_id' => $request->user()->id,
            'item_type' => Interaction::morphTypeFor(Jav::class),
            'item_id' => $request->input('jav_id'),
            'action' => Interaction::ACTION_RATING,
            'value' => $request->input('rating'),
            'meta' => [
                'review' => $request->input('review'),
            ],
        ]);

        return $this->created($rating->load('user', 'item'), 'Rating submitted successfully!');
    }

    public function update(UpdateRatingRequest $request, Interaction $rating): JsonResponse
    {
        $rating->update([
            'value' => $request->input('rating'),
            'meta' => [
                'review' => $request->input('review'),
            ],
        ]);

        return $this->result([
            'message' => 'Rating updated successfully!',
            'data' => $rating->fresh()->load('user', 'item'),
        ]);
    }

    public function destroy(Interaction $rating): JsonResponse
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

        $rating = Interaction::query()
            ->where('user_id', auth()->id())
            ->where('item_type', Interaction::morphTypeFor(Jav::class))
            ->where('item_id', $javId)
            ->where('action', Interaction::ACTION_RATING)
            ->first();

        if (! $rating) {
            return $this->result(['has_rated' => false]);
        }

        return $this->result([
            'has_rated' => true,
            'rating' => $rating->value,
            'review' => $rating->meta['review'] ?? null,
            'id' => $rating->id,
        ]);
    }
}
