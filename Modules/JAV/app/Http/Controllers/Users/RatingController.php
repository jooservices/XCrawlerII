<?php

namespace Modules\JAV\Http\Controllers\Users;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\JAV\Http\Controllers\Users\Api\RatingController as ApiRatingController;
use Modules\JAV\Http\Requests\GetRatingsRequest;
use Modules\JAV\Http\Requests\StoreRatingRequest;
use Modules\JAV\Http\Requests\UpdateRatingRequest;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Rating;

class RatingController extends Controller
{
    /**
     * Display a listing of ratings.
     */
    public function index(GetRatingsRequest $request): View|JsonResponse
    {
        $query = Rating::with(['user', 'jav']);

        // Filter by movie if jav_id provided
        if ($request->filled('jav_id')) {
            $query->forJav($request->jav_id);
        }

        // Filter by user if user_id provided
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by rating stars if provided
        if ($request->filled('rating')) {
            $query->withStars($request->rating);
        }

        // Sort ratings
        $sort = $request->input('sort', 'recent');
        match ($sort) {
            'highest' => $query->orderBy('rating', 'desc'),
            'lowest' => $query->orderBy('rating', 'asc'),
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

        return view('jav::ratings.index', compact('ratings'));
    }

    public function indexVue(GetRatingsRequest $request): InertiaResponse
    {
        $query = Rating::with(['user', 'jav']);

        if ($request->filled('jav_id')) {
            $query->forJav($request->jav_id);
        }

        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->filled('rating')) {
            $query->withStars($request->rating);
        }

        $sort = $request->input('sort', 'recent');
        match ($sort) {
            'highest' => $query->orderBy('rating', 'desc'),
            'lowest' => $query->orderBy('rating', 'asc'),
            default => $query->latest(),
        };

        $perPage = $request->input('per_page', 15);
        $ratings = $query->paginate($perPage);
        $ratings->setCollection(
            $ratings->getCollection()->map(function (Rating $rating) {
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
        $existingRating = Rating::where('user_id', $request->user()->id)
            ->where('jav_id', $request->jav_id)
            ->first();

        if ($existingRating) {
            return back()->with('error', 'You have already rated this movie.');
        }

        $rating = Rating::create([
            'user_id' => $request->user()->id,
            'jav_id' => $request->jav_id,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        // Update movie's average rating
        $this->updateMovieAverageRating($request->jav_id);

        return back()->with('success', 'Rating submitted successfully!');
    }

    /**
     * Display the specified rating.
     */
    public function show(Rating $rating): View|JsonResponse
    {
        $rating->load(['user', 'jav']);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $rating,
            ]);
        }

        return view('jav::ratings.show', compact('rating'));
    }

    public function showVue(Rating $rating): InertiaResponse
    {
        $rating->load(['user', 'jav']);

        return Inertia::render('Ratings/Show', [
            'rating' => $rating,
        ]);
    }

    /**
     * Update the specified rating.
     */
    public function update(UpdateRatingRequest $request, Rating $rating): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return app(ApiRatingController::class)->update($request, $rating);
        }

        $rating->update([
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        // Update movie's average rating
        $this->updateMovieAverageRating($rating->jav_id);

        return back()->with('success', 'Rating updated successfully!');
    }

    /**
     * Remove the specified rating.
     */
    public function destroy(Rating $rating): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return app(ApiRatingController::class)->destroy($rating);
        }

        // Check authorization
        if (! auth()->user() || $rating->user_id !== auth()->user()->id) {
            return back()->with('error', 'Unauthorized.');
        }

        $javId = $rating->jav_id;
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

        $averageRating = Rating::where('jav_id', $javId)->avg('rating');

        // Update the movie's average_rating field
        // Note: This requires adding average_rating column to jav table
        // For now, we'll just calculate it on the fly when needed
    }
}
