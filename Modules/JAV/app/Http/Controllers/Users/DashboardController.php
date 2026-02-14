<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\JAV\Http\Requests\GetActorsRequest;
use Modules\JAV\Http\Requests\GetFavoritesRequest;
use Modules\JAV\Http\Requests\GetHistoryRequest;
use Modules\JAV\Http\Requests\GetJavRequest;
use Modules\JAV\Http\Requests\GetRecommendationsRequest;
use Modules\JAV\Http\Requests\GetTagsRequest;
use Modules\JAV\Http\Requests\NotificationsRequest;
use Modules\JAV\Models\Actor;
use Modules\JAV\Repositories\DashboardReadRepository;
use Modules\JAV\Services\ActorProfileResolver;
use Modules\JAV\Services\DashboardPreferencesService;
use Modules\JAV\Services\RecommendationService;
use Modules\JAV\Services\SearchService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly RecommendationService $recommendationService,
        private readonly DashboardReadRepository $dashboardReadRepository,
        private readonly DashboardPreferencesService $dashboardPreferencesService,
        private readonly ActorProfileResolver $actorProfileResolver,
    ) {
    }

    public function index(GetJavRequest $request): InertiaResponse
    {
        return $this->indexVue($request);
    }

    public function indexVue(GetJavRequest $request): InertiaResponse
    {
        $user = auth()->user();
        $preferences = $this->dashboardPreferencesService->resolve($user);
        $query = (string) ($request->input('q', '') ?? '');
        $tags = $this->dashboardPreferencesService->normalizeTagFilters($request);
        $bioFilters = $this->dashboardPreferencesService->normalizeBioFilters(
            $request->input('bio_filters', []),
            $request->input('bio_key'),
            $request->input('bio_value')
        );
        $filters = [
            'actor' => $request->input('actor'),
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
        $sort = $request->input('sort');
        $direction = $request->input('direction', 'desc');
        $preset = $request->input('preset', 'default');
        $savedPresetIndex = $request->filled('saved_preset') ? (int) $request->input('saved_preset') : null;
        $savedPresets = $preferences['saved_presets'] ?? [];

        if ($savedPresetIndex !== null && isset($savedPresets[$savedPresetIndex])) {
            $savedPreset = $savedPresets[$savedPresetIndex];
            $query = (string) (($savedPreset['query'] ?? $query) ?? '');
            $filters['actor'] = (string) ($savedPreset['actor'] ?? $filters['actor']);
            $filters['tag'] = (string) ($savedPreset['tag'] ?? $filters['tag']);
            $filters['tags'] = $this->dashboardPreferencesService->normalizeTagValues($savedPreset['tags'] ?? $filters['tag'] ?? $filters['tags']);
            $filters['tags_mode'] = (string) ($savedPreset['tags_mode'] ?? $filters['tags_mode']);
            $filters['age'] = $savedPreset['age'] ?? $filters['age'];
            $filters['age_min'] = $savedPreset['age_min'] ?? $filters['age_min'];
            $filters['age_max'] = $savedPreset['age_max'] ?? $filters['age_max'];
            $filters['bio_filters'] = $this->dashboardPreferencesService->normalizeBioFilters(
                $savedPreset['bio_filters'] ?? [],
                $savedPreset['bio_key'] ?? $filters['bio_key'],
                $savedPreset['bio_value'] ?? $filters['bio_value']
            );
            $filters['bio_key'] = $filters['bio_filters'][0]['key'] ?? null;
            $filters['bio_value'] = $filters['bio_filters'][0]['value'] ?? null;
            $sort = (string) ($savedPreset['sort'] ?? $sort);
            $direction = (string) ($savedPreset['direction'] ?? $direction);
            $preset = (string) ($savedPreset['preset'] ?? $preset);
        }

        $items = $this->dashboardReadRepository->searchWithPreset(
            (string) $query,
            $filters,
            30,
            $sort,
            $direction,
            $preset,
            $user
        );
        $this->dashboardReadRepository->decorateItemsForUser($items, $user);

        $continueWatching = collect();
        if ($user !== null) {
            $continueWatching = $this->dashboardReadRepository->continueWatching((int) $user->id, 8);
        }
        $continueWatching = $continueWatching->map(function ($record): array {
            return [
                'id' => (int) $record->id,
                'action' => (string) $record->action,
                'updated_at' => optional($record->updated_at)->toISOString(),
                'updated_at_human' => optional($record->updated_at)->diffForHumans(),
                'jav' => $record->jav,
            ];
        })->values();

        $builtInPresets = [
            'default' => 'Default',
            'weekly_downloads' => 'Most Downloaded This Week',
        ];
        if (auth()->check()) {
            $builtInPresets['preferred_tags'] = 'My Preferred Tags';
        }
        $filters['tags'] = $this->dashboardPreferencesService->normalizeTagValues(array_merge(
            $filters['tags'],
            $this->dashboardPreferencesService->explodeCsv((string) ($filters['tag'] ?? ''))
        ));
        $tagsInput = implode(', ', $filters['tags']);
        $availableBioKeys = $this->dashboardPreferencesService->availableBioKeys();
        $filters['bio_filters'] = $this->dashboardPreferencesService->normalizeBioFilters(
            $filters['bio_filters'] ?? [],
            $filters['bio_key'] ?? null,
            $filters['bio_value'] ?? null
        );
        $actorSuggestions = $this->dashboardReadRepository->actorSuggestions();
        $tagSuggestions = $this->dashboardReadRepository->tagSuggestions();
        $bioValueSuggestions = $this->dashboardReadRepository->bioValueSuggestions($availableBioKeys);

        return Inertia::render('Dashboard/Index', [
            'items' => $items,
            'query' => $query,
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'preset' => $preset,
            'builtInPresets' => $builtInPresets,
            'savedPresets' => $savedPresets,
            'savedPresetIndex' => $savedPresetIndex,
            'continueWatching' => $continueWatching,
            'preferences' => $preferences,
            'tagsInput' => $tagsInput,
            'availableBioKeys' => $availableBioKeys,
            'actorSuggestions' => $actorSuggestions,
            'tagSuggestions' => $tagSuggestions,
            'bioValueSuggestions' => $bioValueSuggestions,
        ]);
    }

    public function actorsVue(GetActorsRequest $request): InertiaResponse
    {
        $query = (string) $request->input('q', '');
        $actors = $this->dashboardReadRepository->searchActors($query);

        return Inertia::render('Actors/Index', [
            'actors' => $actors,
            'query' => $query,
        ]);
    }

    public function tagsVue(GetTagsRequest $request): InertiaResponse
    {
        $query = (string) $request->input('q', '');
        $tags = $this->dashboardReadRepository->searchTags($query);

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
            'query' => $query,
        ]);
    }

    public function actorBioVue(Actor $actor): InertiaResponse
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

    public function historyVue(GetHistoryRequest $request): InertiaResponse
    {
        $user = auth()->user();
        $history = $this->dashboardReadRepository->historyForUser((int) $user->id, 30);
        $history->setCollection(
            $history->getCollection()->map(function ($record) {
                $record->updated_at_human = $record->updated_at?->diffForHumans();
                return $record;
            })
        );

        return Inertia::render('User/History', [
            'history' => $history,
        ]);
    }

    public function favoritesVue(GetFavoritesRequest $request): InertiaResponse
    {
        $user = auth()->user();
        $favorites = $this->dashboardReadRepository->favoritesForUser((int) $user->id, 30);
        $favorites->setCollection(
            $favorites->getCollection()->map(function ($favorite) {
                $favorite->created_at_human = $favorite->created_at?->diffForHumans();
                return $favorite;
            })
        );

        return Inertia::render('User/Favorites', [
            'favorites' => $favorites,
        ]);
    }

    public function recommendationsVue(GetRecommendationsRequest $request): InertiaResponse
    {
        $user = auth()->user();
        $recommendations = $this->recommendationService->getRecommendationsWithReasons($user, 30);

        return Inertia::render('User/Recommendations', [
            'recommendations' => $recommendations,
        ]);
    }

    public function preferencesVue(): InertiaResponse
    {
        $preferences = $this->dashboardPreferencesService->resolve(auth()->user());

        return Inertia::render('User/Preferences', [
            'preferences' => $preferences,
        ]);
    }

    public function notificationsVue(NotificationsRequest $request): InertiaResponse
    {
        $user = $request->user();
        $notifications = $this->dashboardReadRepository->unreadNotificationsForUser($user, 20);

        return Inertia::render('User/Notifications', [
            'notifications' => $notifications,
        ]);
    }

}
