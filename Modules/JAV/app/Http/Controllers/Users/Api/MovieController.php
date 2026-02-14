<?php

namespace Modules\JAV\Http\Controllers\Users\Api;

use Illuminate\Http\JsonResponse;
use Modules\JAV\Http\Controllers\Api\ApiController;
use Modules\JAV\Models\Jav;

class MovieController extends ApiController
{
    public function view(Jav $jav): JsonResponse
    {
        $jav->increment('views');

        return response()->json([
            'views' => $jav->views,
        ]);
    }
}
