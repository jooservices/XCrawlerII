<?php

namespace Modules\JAV\Http\Controllers\Users\Api;

use Illuminate\Http\JsonResponse;
use Modules\JAV\Http\Controllers\Api\ApiController;
use Modules\JAV\Http\Requests\ToggleLikeRequest;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Services\RecommendationService;

class LibraryController extends ApiController
{
    public function __construct(
        private readonly RecommendationService $recommendationService,
    ) {}

    public function toggleLike(ToggleLikeRequest $request): JsonResponse
    {
        $user = $request->user();
        $id = $request->input('id');
        $type = $request->input('type');

        $modelClass = match ($type) {
            'jav' => Jav::class,
            'actor' => Actor::class,
            'tag' => Tag::class,
        };
        $itemType = Interaction::morphTypeFor($modelClass);

        $model = $modelClass::findOrFail($id);
        $interaction = Interaction::query()
            ->where('user_id', $user->id)
            ->where('item_type', $itemType)
            ->where('item_id', $model->id)
            ->where('action', Interaction::ACTION_FAVORITE)
            ->first();

        if ($interaction) {
            $interaction->delete();
            $liked = false;
        } else {
            Interaction::create([
                'user_id' => $user->id,
                'item_type' => $itemType,
                'item_id' => $model->id,
                'action' => Interaction::ACTION_FAVORITE,
            ]);
            $liked = true;
        }

        try {
            $this->recommendationService->syncSnapshotForUserId((int) $user->id, 30);
        } catch (\Throwable) {
            // Non-blocking: like/unlike should still succeed even if snapshot refresh fails.
        }

        return $this->result(['liked' => $liked]);
    }
}
