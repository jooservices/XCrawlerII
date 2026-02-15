<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\JAV\Http\Requests\GetActorsRequest;
use Modules\JAV\Models\Actor;
use Modules\JAV\Repositories\DashboardReadRepository;
use Modules\JAV\Services\ActorProfileResolver;
use Modules\JAV\Services\DashboardPreferencesService;

class ActorController extends Controller
{
    public function __construct(
        private readonly DashboardReadRepository $dashboardReadRepository,
        private readonly ActorProfileResolver $actorProfileResolver,
        private readonly DashboardPreferencesService $dashboardPreferencesService,
    ) {
    }

    public function index(GetActorsRequest $request): InertiaResponse
    {
        $query = (string) $request->input('q', '');
        $tags = $this->dashboardPreferencesService->normalizeTagFilters($request);
        $bioFilters = $this->dashboardPreferencesService->normalizeBioFilters(
            $request->input('bio_filters', []),
            $request->input('bio_key'),
            $request->input('bio_value')
        );
        $filters = [
            'tag' => $request->input('tag'),
            'tags' => $tags,
            'tags_mode' => $request->input('tags_mode', 'any'),
            'age' => $request->input('age'),
            'age_min' => $request->input('age_min'),
            'age_max' => $request->input('age_max'),
            'bio_key' => $bioFilters[0]['key'] ?? null,
            'bio_value' => $bioFilters[0]['value'] ?? null,
            'bio_filters' => $bioFilters,
        ];
        $sort = (string) $request->input('sort', 'javs_count');
        $direction = (string) $request->input('direction', 'desc');
        $actors = $this->dashboardReadRepository->searchActors($query, $filters, 60, $sort, $direction);

        $filters['tags'] = $this->dashboardPreferencesService->normalizeTagValues(array_merge(
            $filters['tags'],
            $this->dashboardPreferencesService->explodeCsv((string) ($filters['tag'] ?? ''))
        ));
        $tagsInput = implode(', ', $filters['tags']);
        $availableBioKeys = $this->dashboardPreferencesService->availableBioKeys();
        $tagSuggestions = $this->dashboardReadRepository->tagSuggestions();
        $bioValueSuggestions = $this->dashboardReadRepository->bioValueSuggestions($availableBioKeys);

        return Inertia::render('Actors/Index', [
            'actors' => $actors,
            'query' => $query,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'tagsInput' => $tagsInput,
            'availableBioKeys' => $availableBioKeys,
            'tagSuggestions' => $tagSuggestions,
            'bioValueSuggestions' => $bioValueSuggestions,
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
