<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\JAV\Http\Requests\GetTagsRequest;
use Modules\JAV\Repositories\DashboardReadRepository;

class TagController extends Controller
{
    public function __construct(private readonly DashboardReadRepository $dashboardReadRepository)
    {
    }

    public function index(GetTagsRequest $request): InertiaResponse
    {
        $query = (string) $request->input('q', '');
        $tags = $this->dashboardReadRepository->searchTags($query);

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
            'query' => $query,
        ]);
    }
}
