<?php

namespace Modules\JAV\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\JAV\Http\Requests\AnalyticsRequest;
use Modules\JAV\Services\AnalyticsReadService;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsReadService $analyticsReadService) {}

    public function index(AnalyticsRequest $request): InertiaResponse
    {
        return $this->indexVue($request);
    }

    public function indexVue(AnalyticsRequest $request): InertiaResponse
    {
        return Inertia::render('Admin/Analytics', $this->buildPayload($request));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(AnalyticsRequest $request): array
    {
        $days = (int) $request->validated('days', 14);
        $analytics = $this->analyticsReadService->getSnapshot($days);
        $totals = $analytics['totals'] ?? ['jav' => 0, 'actors' => 0, 'tags' => 0];
        $todayCreated = $analytics['todayCreated'] ?? ['jav' => 0, 'actors' => 0, 'tags' => 0];
        $dailyCreated = $analytics['dailyCreated'] ?? [
            'jav' => ['labels' => [], 'values' => []],
            'actors' => ['labels' => [], 'values' => []],
            'tags' => ['labels' => [], 'values' => []],
        ];
        $providerDailyCreated = $analytics['providerDailyCreated'] ?? ['labels' => [], 'series' => []];
        $dailyEngagement = $analytics['dailyEngagement'] ?? [
            'favorites' => ['labels' => [], 'values' => []],
            'watchlists' => ['labels' => [], 'values' => []],
            'ratings' => ['labels' => [], 'values' => []],
            'history' => ['labels' => [], 'values' => []],
        ];
        $providerStats = $analytics['providerStats'] ?? [];
        $topViewed = $analytics['topViewed'] ?? [];
        $topDownloaded = $analytics['topDownloaded'] ?? [];
        $topRated = $analytics['topRated'] ?? [];
        $quality = $analytics['quality'] ?? [
            'missing_actors' => 0,
            'missing_tags' => 0,
            'missing_image' => 0,
            'missing_date' => 0,
            'orphan_actors' => 0,
            'orphan_tags' => 0,
            'avg_actors_per_jav' => 0,
            'avg_tags_per_jav' => 0,
        ];
        $syncHealth = $analytics['syncHealth'] ?? ['pending_jobs' => 0, 'failed_jobs_24h' => 0];

        return compact(
            'days',
            'totals',
            'todayCreated',
            'dailyCreated',
            'providerDailyCreated',
            'dailyEngagement',
            'providerStats',
            'topViewed',
            'topDownloaded',
            'topRated',
            'quality',
            'syncHealth'
        );
    }
}
