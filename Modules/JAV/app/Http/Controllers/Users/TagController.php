<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\JAV\Http\Requests\GetTagsRequest;
use Modules\JAV\Repositories\DashboardReadRepository;

class TagController extends Controller
{
    public function __construct(private readonly DashboardReadRepository $dashboardReadRepository)
    {
    }

    public function index(GetTagsRequest $request): View|JsonResponse
    {
        $query = (string) $request->input('q', '');
        $tags = $this->dashboardReadRepository->searchTags($query);

        if ($request->ajax()) {
            $html = '';
            foreach ($tags as $tag) {
                $html .= view('jav::dashboard.partials.tag_card', compact('tag'))->render();
            }

            return response()->json([
                'html' => $html,
                'next_page_url' => $this->toRelativeUrl($tags->nextPageUrl()),
            ]);
        }

        return view('jav::dashboard.tags', compact('tags', 'query'));
    }

    private function toRelativeUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);
        $fragment = parse_url($url, PHP_URL_FRAGMENT);

        if (!empty($query)) {
            $path .= '?' . $query;
        }
        if (!empty($fragment)) {
            $path .= '#' . $fragment;
        }

        return $path;
    }
}
