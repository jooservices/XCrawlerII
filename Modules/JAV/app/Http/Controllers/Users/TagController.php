<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\JAV\Http\Requests\GetTagsRequest;
use Modules\JAV\Repositories\DashboardReadRepository;

class TagController extends Controller
{
    public function __construct(private readonly DashboardReadRepository $dashboardReadRepository) {}

    public function index(GetTagsRequest $request): InertiaResponse
    {
        $query = (string) $request->input('q', $request->input('query', ''));
        $sort = (string) $request->input('sort', 'javs_count');
        $direction = (string) $request->input('direction', 'desc');
        $tags = $this->dashboardReadRepository->searchTags($query, $sort, $direction);

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
            'query' => $query,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }
}
