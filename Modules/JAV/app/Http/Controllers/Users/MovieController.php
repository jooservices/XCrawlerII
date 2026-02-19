<?php

namespace Modules\JAV\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use JOOservices\Client\Client\ClientBuilder;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Services\AnalyticsIngestService;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserJavHistory;
use Modules\JAV\Repositories\DashboardReadRepository;
use Modules\JAV\Services\FfjavService;
use Modules\JAV\Services\OneFourOneJavService;
use Modules\JAV\Services\OnejavService;
use Modules\JAV\Services\SearchService;

class MovieController extends Controller
{
    public function __construct(
        private readonly DashboardReadRepository $dashboardReadRepository,
        private readonly SearchService $searchService,
        private readonly AnalyticsIngestService $analyticsIngestService,
    ) {}

    public function show(Jav $jav): InertiaResponse
    {
        $this->trackView($jav);

        if (auth()->check()) {
            UserJavHistory::firstOrCreate([
                'user_id' => auth()->id(),
                'jav_id' => $jav->id,
                'action' => 'view',
            ], [
                'updated_at' => now(),
            ]);
        }

        $this->dashboardReadRepository->loadJavRelations($jav);

        $isLiked = false;
        if (auth()->check()) {
            $isLiked = $this->dashboardReadRepository->isJavLikedByUser($jav, (int) auth()->id());
        }

        $relatedByActors = $this->searchService->getRelatedByActors($jav, 10);
        $relatedByTags = $this->searchService->getRelatedByTags($jav, 10);

        return Inertia::render('Movies/Show', [
            'jav' => $jav,
            'relatedByActors' => $relatedByActors,
            'relatedByTags' => $relatedByTags,
            'isLiked' => $isLiked,
        ]);
    }

    public function download(Jav $jav): Response|RedirectResponse
    {
        $this->trackDownload($jav);

        if (auth()->check()) {
            UserJavHistory::updateOrCreate([
                'user_id' => auth()->id(),
                'jav_id' => $jav->id,
                'action' => 'download',
            ]);
        }

        try {
            $service = $this->resolveServiceBySource($jav->source);

            $item = $service->item($jav->url);
            Log::info('Download requested', ['url' => $jav->url, 'download_link' => $item->download]);

            if (empty($item->download)) {
                return back()->with('error', 'Download link not found.');
            }

            $downloadLink = $item->download;
            if (str_starts_with($downloadLink, '/')) {
                $baseUrl = $this->baseUrlBySource($jav->source);
                $downloadLink = $baseUrl.$downloadLink;
            }

            $client = ClientBuilder::create()->build();
            $response = $client->get($downloadLink);
            $content = $response->toPsrResponse()->getBody()->getContents();
            $headers = $response->toPsrResponse()->getHeaders();

            $responseHeaders = [
                'Content-Type' => $headers['Content-Type'][0] ?? 'application/x-bittorrent',
                'Content-Disposition' => $headers['Content-Disposition'][0] ?? 'attachment; filename="'.$jav->code.'.torrent"',
            ];

            return response($content, 200, $responseHeaders);
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch download link: '.$e->getMessage());
        }
    }

    private function resolveServiceBySource(string $source): object
    {
        return match ($source) {
            'onejav' => app(OnejavService::class),
            '141jav' => app(OneFourOneJavService::class),
            'ffjav' => app(FfjavService::class),
            default => throw new InvalidArgumentException("Unsupported source: {$source}"),
        };
    }

    private function baseUrlBySource(string $source): string
    {
        return match ($source) {
            'onejav' => 'https://onejav.com',
            '141jav' => 'https://www.141jav.com',
            'ffjav' => 'https://ffjav.com',
            default => '',
        };
    }

    private function trackView(Jav $jav): void
    {
        if (! (bool) config('analytics.enabled', false)) {
            $jav->increment('views');

            return;
        }

        $this->analyticsIngestService->ingest($this->eventPayload($jav, AnalyticsAction::View));
    }

    private function trackDownload(Jav $jav): void
    {
        if (! (bool) config('analytics.enabled', false)) {
            $jav->increment('downloads');

            return;
        }

        $this->analyticsIngestService->ingest($this->eventPayload($jav, AnalyticsAction::Download));
    }

    /**
     * @return array<string, int|string>
     */
    private function eventPayload(Jav $jav, AnalyticsAction $action): array
    {
        return [
            'event_id' => (string) Str::uuid(),
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => (string) $jav->uuid,
            'action' => $action->value,
            'value' => 1,
            'occurred_at' => now('UTC')->format('Y-m-d\\TH:i:s\\Z'),
        ];
    }
}
