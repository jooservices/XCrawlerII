<?php

namespace Modules\JAV\Http\Controllers\Users\Api;

use Illuminate\Http\JsonResponse;
use Modules\JAV\Http\Controllers\Api\ApiController;
use Modules\JAV\Http\Requests\ToggleLikeRequest;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;

class LibraryController extends ApiController
{
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

        $model = $modelClass::findOrFail($id);
        $favorite = $model->favorites()->where('user_id', $user->id)->first();

        if ($favorite) {
            $favorite->delete();
            $liked = false;
        } else {
            $model->favorites()->create(['user_id' => $user->id]);
            $liked = true;
        }

        return $this->result(['liked' => $liked]);
    }
}
