<?php

namespace Modules\JAV\Http\Controllers\Users\Api;

use Illuminate\Http\JsonResponse;
use Modules\JAV\Http\Controllers\Api\ApiController;
use Modules\JAV\Http\Requests\AddToWatchlistRequest;
use Modules\JAV\Http\Requests\UpdateWatchlistRequest;
use Modules\JAV\Models\Watchlist;

class WatchlistController extends ApiController
{
    public function store(AddToWatchlistRequest $request): JsonResponse
    {
        $watchlist = Watchlist::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'jav_id' => $request->input('jav_id'),
            ],
            [
                'status' => $request->input('status', 'to_watch'),
            ]
        );

        return $this->result([
            'message' => 'Added to watchlist',
            'watchlist' => $watchlist,
        ]);
    }

    public function update(UpdateWatchlistRequest $request, Watchlist $watchlist): JsonResponse
    {
        $watchlist->update([
            'status' => $request->input('status'),
        ]);

        return $this->result([
            'message' => 'Status updated successfully',
            'watchlist' => $watchlist,
        ]);
    }

    public function destroy(Watchlist $watchlist): JsonResponse
    {
        if ($watchlist->user_id !== auth()->id()) {
            return $this->error('Unauthorized', 403);
        }

        $watchlist->delete();

        return $this->result([
            'message' => 'Removed from watchlist',
        ]);
    }

    public function check(int $javId): JsonResponse
    {
        $watchlist = Watchlist::query()
            ->where('user_id', auth()->id())
            ->where('jav_id', $javId)
            ->first();

        return $this->result([
            'in_watchlist' => $watchlist !== null,
            'status' => $watchlist?->status,
            'watchlist_id' => $watchlist?->id,
        ]);
    }
}
