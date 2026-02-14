<?php

namespace Modules\JAV\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\JAV\Http\Requests\AddToWatchlistRequest;
use Modules\JAV\Http\Requests\GetWatchlistRequest;
use Modules\JAV\Http\Requests\UpdateWatchlistRequest;
use Modules\JAV\Models\Watchlist;

class WatchlistController extends Controller
{
    /**
     * Display the user's watchlist.
     */
    public function index(GetWatchlistRequest $request): View
    {
        $query = Watchlist::with('jav')
            ->forUser(auth()->id())
            ->latest();

        $status = $request->input('status', 'all');
        if ($status !== 'all') {
            $query->status($status);
        }

        $watchlist = $query->paginate(30);

        return view('jav::watchlist.index', compact('watchlist', 'status'));
    }

    /**
     * Add a movie to the watchlist.
     */
    public function store(AddToWatchlistRequest $request): JsonResponse
    {
        $watchlist = Watchlist::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'jav_id' => $request->input('jav_id'),
            ],
            [
                'status' => $request->input('status', 'to_watch'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Added to watchlist',
            'watchlist' => $watchlist,
        ]);
    }

    /**
     * Update watchlist item status.
     */
    public function update(UpdateWatchlistRequest $request, Watchlist $watchlist): JsonResponse|RedirectResponse
    {
        $watchlist->update([
            'status' => $request->input('status'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'watchlist' => $watchlist,
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Status updated successfully');
    }

    /**
     * Remove a movie from the watchlist.
     */
    public function destroy(Watchlist $watchlist): JsonResponse|RedirectResponse
    {
        if ($watchlist->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $watchlist->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Removed from watchlist',
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Removed from watchlist');
    }

    /**
     * Check if a movie is in user's watchlist.
     */
    public function check(int $javId): JsonResponse
    {
        $watchlist = Watchlist::where('user_id', auth()->id())
            ->where('jav_id', $javId)
            ->first();

        return response()->json([
            'in_watchlist' => $watchlist !== null,
            'status' => $watchlist?->status,
            'watchlist_id' => $watchlist?->id,
        ]);
    }
}
