<?php

namespace Modules\JAV\Http\Controllers\Users\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\JAV\Http\Requests\GetJavRequest;
use Modules\JAV\Repositories\DashboardReadRepository;
use Modules\JAV\Services\DashboardPreferencesService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardReadRepository $dashboardReadRepository,
        private readonly DashboardPreferencesService $dashboardPreferencesService,
    ) {}

    public function items(GetJavRequest $request): JsonResponse
    {
        $user = $request->user();
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

        return response()->json($items);
    }
}
