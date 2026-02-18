<?php

namespace Modules\JAV\Http\Controllers\Users;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\JAV\Http\Controllers\Users\Api\RatingController as ApiRatingController;
use Modules\JAV\Http\Requests\GetRatingsRequest;
use Modules\JAV\Http\Requests\StoreRatingRequest;
use Modules\JAV\Http\Requests\UpdateRatingRequest;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;

class RatingController extends Controller
{
    /**
     * Display a listing of ratings.
     */
    public function index(GetRatingsRequest $request): InertiaResponse|JsonResponse
    {
        $query = Interaction::with(['user', 'item'])
            ->where('action', Interaction::ACTION_RATING)
            ->where('item_type', Interaction::morphTypeFor(Jav::class));

        // Filter by movie if jav_id provided
        if ($request->filled('jav_id')) {
            $query->where('item_id', $request->jav_id);
        }

        // Filter by user if user_id provided
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by rating stars if provided
        if ($request->filled('rating')) {
            $query->where('value', $request->rating);
        }

        // Sort ratings
        $sort = $request->input('sort', 'recent');
        match ($sort) {
            'highest' => $query->orderBy('value', 'desc'),
            'lowest' => $query->orderBy('value', 'asc'),
            default => $query->latest(),
        };

        $perPage = $request->input('per_page', 15);
        $ratings = $query->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $ratings,
            ]);
        }

        return Inertia::render('Ratings/Index', [
            'ratings' => $ratings,
        ]);
    }

    public function indexVue(GetRatingsRequest $request): InertiaResponse
    {
        $query = Interaction::with(['user', 'item'])
            ->where('action', Interaction::ACTION_RATING)
            ->where('item_type', Interaction::morphTypeFor(Jav::class));

        if ($request->filled('jav_id')) {
            $query->where('item_id', $request->jav_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('rating')) {
            $query->where('value', $request->rating);
        }

        $sort = $request->input('sort', 'recent');
        match ($sort) {
            'highest' => $query->orderBy('value', 'desc'),
            'lowest' => $query->orderBy('value', 'asc'),
            default => $query->latest(),
        };

        $perPage = $request->input('per_page', 15);
        $ratings = $query->paginate($perPage);
        $ratings->setCollection(
            $ratings->getCollection()->map(function (Interaction $rating) {
                $rating->created_at_human = $rating->created_at?->diffForHumans();

                return $rating;
            })
        );

        return Inertia::render('Ratings/Index', [
            'ratings' => $ratings,
        ]);
    }

    /**
     * Store a newly created rating.
     */
    public function store(StoreRatingRequest $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return app(ApiRatingController::class)->store($request);
        }

        // Check if user has already rated this movie
        $existingRating = Interaction::query()
            ->where('user_id', $request->user()->id)
            ->where('item_type', Interaction::morphTypeFor(Jav::class))
            ->where('item_id', $request->jav_id)
            ->where('action', Interaction::ACTION_RATING)
            ->first();

        if ($existingRating) {
            return back()->with('error', 'You have already rated this movie.');
        }

        $rating = Interaction::create([
            'user_id' => $request->user()->id,
            'item_type' => Interaction::morphTypeFor(Jav::class),
            'item_id' => $request->jav_id,
            'action' => Interaction::ACTION_RATING,
            'value' => $request->rating,
            'meta' => [
                'review' => $request->review,
            ],
        ]);

        // Update movie's average rating
        $this->updateMovieAverageRating($request->jav_id);

        return back()->with('success', 'Rating submitted successfully!');
    }

    /**
     * Display the specified rating.
     */
    public function show(Interaction $rating): InertiaResponse|JsonResponse
    {
        $rating->load(['user', 'item']);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $rating,
            ]);
        }

        return Inertia::render('Ratings/Show', [
            'rating' => $rating,
        ]);
    }

    public function showVue(Interaction $rating): InertiaResponse
    {
        $rating->load(['user', 'item']);

        return Inertia::render('Ratings/Show', [
            'rating' => $rating,
        ]);
    }

    /**
     * Update the specified rating.
     */
    public function update(UpdateRatingRequest $request, Interaction $rating): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return app(ApiRatingController::class)->update($request, $rating);
        }

        $rating->update([
            'value' => $request->rating,
            'meta' => [
                'review' => $request->review,
            ],
        ]);

        // Update movie's average rating
        $this->updateMovieAverageRating((int) $rating->item_id);

        return back()->with('success', 'Rating updated successfully!');
    }

    /**
     * Remove the specified rating.
     */
    public function destroy(Interaction $rating): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return app(ApiRatingController::class)->destroy($rating);
        }

        // Check authorization
        if (! auth()->user() || $rating->user_id !== auth()->user()->id) {
            return back()->with('error', 'Unauthorized.');
        }

        $javId = (int) $rating->item_id;
        $rating->delete();

        // Update movie's average rating
        $this->updateMovieAverageRating($javId);

        return back()->with('success', 'Rating deleted successfully!');
    }

    /**
     * Get user's rating for a specific movie.
     */
    public function check(int $javId): JsonResponse
    {
        return app(ApiRatingController::class)->check($javId);
    }

    /**
     * Update the average rating for a movie.
     */
    protected function updateMovieAverageRating(int $javId): void
    {
        $jav = Jav::find($javId);

        if (! $jav) {
            return;
        }

        $averageRating = Interaction::query()
            ->where('item_type', Interaction::morphTypeFor(Jav::class))
            ->where('item_id', $javId)
            ->where('action', Interaction::ACTION_RATING)
            ->avg('value');

        // Update the movie's average_rating field
        // Note: This requires adding average_rating column to jav table
        // For now, we'll just calculate it on the fly when needed
    }
}
