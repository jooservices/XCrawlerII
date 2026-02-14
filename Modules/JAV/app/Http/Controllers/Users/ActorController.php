<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
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

    public function index(GetActorsRequest $request): InertiaResponse
    {
        $query = (string) $request->input('q', '');
        $actors = $this->dashboardReadRepository->searchActors($query);

        return Inertia::render('Actors/Index', [
            'actors' => $actors,
            'query' => $query,
        ]);
    }

    public function bio(Actor $actor): InertiaResponse
    {
        $actor->loadCount('javs')->load(['profileAttributes', 'profileSources']);

        $movies = $this->dashboardReadRepository->actorMovies($actor, 30);

        $bioProfile = $this->actorProfileResolver->toDisplayMap($actor);
        $resolved = $this->actorProfileResolver->resolve($actor);
        $primarySource = $resolved['primary_source'];

        $primarySyncedAt = $actor->profileSources
            ->firstWhere('source', $primarySource)?->synced_at
            ?? $actor->xcity_synced_at;
        $primarySyncedAtFormatted = $primarySyncedAt?->format('Y-m-d H:i');

        return Inertia::render('Actors/Bio', [
            'actor' => $actor,
            'movies' => $movies,
            'bioProfile' => $bioProfile,
            'primarySource' => $primarySource,
            'primarySyncedAt' => $primarySyncedAt,
            'primarySyncedAtFormatted' => $primarySyncedAtFormatted,
        ]);
    }
}
