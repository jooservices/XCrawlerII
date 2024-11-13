<?php

namespace Modules\Udemy\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Udemy\Http\Requests\UdemyCreateRequest;
use Modules\Udemy\Jobs\SyncMyCoursesJob;
use Modules\Udemy\Repositories\UserTokenRepository;
use Modules\Udemy\Transformers\UserTokenResource;

class UdemyController extends Controller
{
    public function create(UdemyCreateRequest $request)
    {
        $userToken = app(UserTokenRepository::class)->createWithToken($request->input('token'));

        SyncMyCoursesJob::dispatch($userToken);

        return new UserTokenResource($userToken);
    }
}
