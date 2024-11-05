<?php

namespace Modules\Udemy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Udemy\Http\Requests\UdemyCreateRequest;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Repositories\UserTokenRepository;
use Modules\Udemy\Services\UdemyService;
use Modules\Udemy\Transformers\UserTokenResource;

class UdemyController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(UdemyCreateRequest $request)
    {
        $userToken = app(UserTokenRepository::class)
            ->createWithToken($request->input('token'));
        app(UdemyService::class)->syncMyCourses($userToken);

        return new UserTokenResource($userToken);
    }
}
