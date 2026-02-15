<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\JAV\Http\Controllers\Users\Api\WatchlistController as ApiWatchlistController;
use Modules\JAV\Http\Requests\AddToWatchlistRequest;
use Modules\JAV\Http\Requests\GetWatchlistRequest;
use Modules\JAV\Http\Requests\UpdateWatchlistRequest;
use Modules\JAV\Models\Watchlist;

class WatchlistController extends Controller
{
    /**
     * Display the user's watchlist.
     */
    public function index(GetWatchlistRequest $request): InertiaResponse
    {
        return $this->indexVue($request);
    }

    public function indexVue(GetWatchlistRequest $request): InertiaResponse
    {
        $query = Watchlist::with('jav')
            ->forUser(auth()->id())
            ->latest();

        $status = $request->input('status', 'all');
        if ($status !== 'all') {
            $query->status($status);
        }

        $watchlist = $query->paginate(30);
        $watchlist->setCollection(
            $watchlist->getCollection()->map(function (Watchlist $item) {
                $item->created_at_human = $item->created_at?->diffForHumans();

                return $item;
            })
        );

        return Inertia::render('User/Watchlist', [
            'watchlist' => $watchlist,
            'status' => $status,
        ]);
    }

    /**
     * Add a movie to the watchlist.
     */
    public function store(AddToWatchlistRequest $request): JsonResponse
    {
        return app(ApiWatchlistController::class)->store($request);
    }

    /**
     * Update watchlist item status.
     */
    public function update(UpdateWatchlistRequest $request, Watchlist $watchlist): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return app(ApiWatchlistController::class)->update($request, $watchlist);
        }

        $watchlist->update([
            'status' => $request->input('status'),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Status updated successfully');
    }

    /**
     * Remove a movie from the watchlist.
     */
    public function destroy(Watchlist $watchlist): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return app(ApiWatchlistController::class)->destroy($watchlist);
        }

        if ($watchlist->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $watchlist->delete();

        return redirect()
            ->back()
            ->with('success', 'Removed from watchlist');
    }

    /**
     * Check if a movie is in user's watchlist.
     */
    public function check(int $javId): JsonResponse
    {
        return app(ApiWatchlistController::class)->check($javId);
    }
}
