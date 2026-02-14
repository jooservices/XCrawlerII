<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\JAV\Http\Requests\GetActorsRequest;
use Modules\JAV\Models\Actor;
use Modules\JAV\Repositories\DashboardReadRepository;
use Modules\JAV\Services\ActorProfileResolver;

class ActorController extends Controller
{
    public function __construct(
        private readonly DashboardReadRepository $dashboardReadRepository,
        private readonly ActorProfileResolver $actorProfileResolver,
    ) {
    }

    public function index(GetActorsRequest $request): View|JsonResponse
    {
        $query = (string) $request->input('q', '');
        $actors = $this->dashboardReadRepository->searchActors($query);

        if ($request->ajax()) {
            $html = '';
            foreach ($actors as $actor) {
                $html .= view('jav::dashboard.partials.actor_card', compact('actor'))->render();
            }

            return response()->json([
                'html' => $html,
                'next_page_url' => $this->toRelativeUrl($actors->nextPageUrl()),
            ]);
        }

        return view('jav::dashboard.actors', compact('actors', 'query'));
    }

    public function bio(Actor $actor): View
    {
        $actor->loadCount('javs')->load(['profileAttributes', 'profileSources']);

        $movies = $this->dashboardReadRepository->actorMovies($actor, 30);

        $bioProfile = $this->actorProfileResolver->toDisplayMap($actor);
        $resolved = $this->actorProfileResolver->resolve($actor);
        $primarySource = $resolved['primary_source'];

        $primarySyncedAt = $actor->profileSources
            ->firstWhere('source', $primarySource)?->synced_at
            ?? $actor->xcity_synced_at;

        return view('jav::dashboard.actor_bio', compact('actor', 'movies', 'bioProfile', 'primarySource', 'primarySyncedAt'));
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
