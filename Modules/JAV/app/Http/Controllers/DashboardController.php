<?php

namespace Modules\JAV\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Modules\JAV\Http\Requests\GetActorsRequest;
use Modules\JAV\Http\Requests\GetFavoritesRequest;
use Modules\JAV\Http\Requests\GetHistoryRequest;
use Modules\JAV\Http\Requests\GetJavRequest;
use Modules\JAV\Http\Requests\GetRecommendationsRequest;
use Modules\JAV\Http\Requests\GetTagsRequest;
use Modules\JAV\Http\Requests\RequestSyncRequest;
use Modules\JAV\Http\Requests\ToggleLikeRequest;
use Modules\JAV\Models\UserLikeNotification;
use Modules\JAV\Services\SearchService;

class DashboardController extends Controller
{
    protected SearchService $searchService;
    protected \Modules\JAV\Services\RecommendationService $recommendationService;

    public function __construct(SearchService $searchService, \Modules\JAV\Services\RecommendationService $recommendationService)
    {
        $this->searchService = $searchService;
        $this->recommendationService = $recommendationService;
    }

    public function index(GetJavRequest $request): View|JsonResponse
    {
        $query = $request->input('q', '');
        $filters = [
            'actor' => $request->input('actor'),
            'tag' => $request->input('tag'),
        ];
        $sort = $request->input('sort');
        $direction = $request->input('direction', 'desc');

        $items = $this->searchService->searchJav($query, $filters, 30, $sort, $direction);

        if ($request->ajax()) {
            $html = '';
            foreach ($items as $item) {
                $html .= view('jav::dashboard.partials.movie_card', compact('item'))->render();
            }

            return response()->json([
                'html' => $html,
                'next_page_url' => $this->toRelativeUrl($items->nextPageUrl()),
            ]);
        }

        return view('jav::dashboard.index', compact('items', 'query', 'filters', 'sort', 'direction'));
    }

    public function actors(GetActorsRequest $request): View|JsonResponse
    {
        $query = $request->input('q', '');
        $actors = $this->searchService->searchActors($query);

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

    public function tags(GetTagsRequest $request): View|JsonResponse
    {
        $query = $request->input('q', '');
        $tags = $this->searchService->searchTags($query);

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

    public function actorBio(\Modules\JAV\Models\Actor $actor): View
    {
        $actor->loadCount('javs')->load(['profileAttributes', 'profileSources']);

        $movies = $actor->javs()
            ->with(['actors', 'tags'])
            ->orderByDesc('date')
            ->paginate(30);

        $resolver = app(\Modules\JAV\Services\ActorProfileResolver::class);
        $bioProfile = $resolver->toDisplayMap($actor);
        $resolved = $resolver->resolve($actor);
        $primarySource = $resolved['primary_source'];

        $primarySyncedAt = $actor->profileSources
            ->firstWhere('source', $primarySource)?->synced_at
            ?? $actor->xcity_synced_at;

        return view('jav::dashboard.actor_bio', compact('actor', 'movies', 'bioProfile', 'primarySource', 'primarySyncedAt'));
    }

    public function show(\Modules\JAV\Models\Jav $jav): View
    {
        // Increment view count
        $jav->increment('views');

        // Track history if user is authenticated
        if (auth()->check()) {
            \Modules\JAV\Models\UserJavHistory::firstOrCreate([
                'user_id' => auth()->id(),
                'jav_id' => $jav->id,
                'action' => 'view',
            ], [
                'updated_at' => now(), // Touch timestamp if exists
            ]);
        }

        // Load relationships
        $jav->load(['actors', 'tags']);

        // Check if liked
        $isLiked = false;
        if (auth()->check()) {
            $isLiked = $jav->favorites()->where('user_id', auth()->id())->exists();
        }

        // Get related movies
        $relatedByActors = $this->searchService->getRelatedByActors($jav, 10);
        $relatedByTags = $this->searchService->getRelatedByTags($jav, 10);

        return view('jav::dashboard.show', compact('jav', 'relatedByActors', 'relatedByTags', 'isLiked'));
    }

    public function view(\Modules\JAV\Models\Jav $jav): JsonResponse
    {
        $jav->increment('views');

        return response()->json(['views' => $jav->views]);
    }

    public function download(\Modules\JAV\Models\Jav $jav): Response|RedirectResponse
    {
        $jav->increment('downloads');

        if (auth()->check()) {
            \Modules\JAV\Models\UserJavHistory::updateOrCreate([
                'user_id' => auth()->id(),
                'jav_id' => $jav->id,
                'action' => 'download',
            ]);
        }

        try {
            $service = $this->resolveServiceBySource($jav->source);

            $item = $service->item($jav->url);
            \Illuminate\Support\Facades\Log::info('Download requested', ['url' => $jav->url, 'download_link' => $item->download]);

            if (empty($item->download)) {
                return back()->with('error', 'Download link not found.');
            }

            $downloadLink = $item->download;
            if (str_starts_with($downloadLink, '/')) {
                $baseUrl = $this->baseUrlBySource($jav->source);
                $downloadLink = $baseUrl . $downloadLink;
            }

            // Stream the file content
            $client = \JOOservices\Client\Client\ClientBuilder::create()->build();
            $response = $client->get($downloadLink);
            $content = $response->toPsrResponse()->getBody()->getContents();
            $headers = $response->toPsrResponse()->getHeaders();

            // Clean headers for response
            $responseHeaders = [
                'Content-Type' => $headers['Content-Type'][0] ?? 'application/x-bittorrent',
                'Content-Disposition' => $headers['Content-Disposition'][0] ?? 'attachment; filename="' . $jav->code . '.torrent"',
            ];

            return response($content, 200, $responseHeaders);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to fetch download link: ' . $e->getMessage());
        }
    }

    public function request(RequestSyncRequest $request): JsonResponse
    {
        Cache::put('jav:sync:active', [
            'provider' => $request->source,
            'type' => $request->type,
            'started_at' => now()->toIso8601String(),
        ], now()->addHours(6));

        \Illuminate\Support\Facades\Artisan::call('jav:sync', [
            'provider' => $request->source,
            '--type' => $request->type,
        ]);

        return response()->json([
            'message' => 'Sync request queued successfully.',
            'progress' => $this->buildSyncProgressSnapshot(),
        ]);
    }

    public function status(): JsonResponse
    {
        $progress = $this->buildSyncProgressSnapshot();

        return response()->json([
            'onejav' => [
                'new' => \Modules\Core\Facades\Config::get('onejav', 'new_page', 1),
                'popular' => \Modules\Core\Facades\Config::get('onejav', 'popular_page', 1),
            ],
            '141jav' => [
                'new' => \Modules\Core\Facades\Config::get('onefourone', 'new_page', 1),
                'popular' => \Modules\Core\Facades\Config::get('onefourone', 'popular_page', 1),
            ],
            'ffjav' => [
                'new' => \Modules\Core\Facades\Config::get('ffjav', 'new_page', 1),
                'popular' => \Modules\Core\Facades\Config::get('ffjav', 'popular_page', 1),
            ],
            'progress' => $progress,
        ]);
    }

    public function syncProgress(): View
    {
        return view('jav::dashboard.sync_progress');
    }

    public function syncProgressData(): JsonResponse
    {
        return response()->json($this->buildSyncProgressSnapshot());
    }

    private function resolveServiceBySource(string $source): object
    {
        return match ($source) {
            'onejav' => app(\Modules\JAV\Services\OnejavService::class),
            '141jav' => app(\Modules\JAV\Services\OneFourOneJavService::class),
            'ffjav' => app(\Modules\JAV\Services\FfjavService::class),
            default => throw new \InvalidArgumentException("Unsupported source: {$source}"),
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
    public function toggleLike(ToggleLikeRequest $request): JsonResponse
    {
        $user = auth()->user();
        $id = $request->input('id');
        $type = $request->input('type');

        $modelClass = match ($type) {
            'jav' => \Modules\JAV\Models\Jav::class,
            'actor' => \Modules\JAV\Models\Actor::class,
            'tag' => \Modules\JAV\Models\Tag::class,
        };

        $model = $modelClass::findOrFail($id);
        $favorite = $model->favorites()->where('user_id', $user->id)->first();

        if ($favorite) {
            $favorite->delete();
            $liked = false;
        } else {
            $model->favorites()->create(['user_id' => $user->id]);
            $liked = true;
        }

        return response()->json(['success' => true, 'liked' => $liked]);
    }

    public function history(GetHistoryRequest $request): View
    {
        $user = auth()->user();
        $history = \Modules\JAV\Models\UserJavHistory::with('jav')
            ->where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->paginate(30);

        return view('jav::dashboard.history', compact('history'));
    }

    public function favorites(GetFavoritesRequest $request): View
    {
        $user = auth()->user();
        $favorites = \Modules\JAV\Models\Favorite::with(['favoritable'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return view('jav::dashboard.favorites', compact('favorites'));
    }
    public function recommendations(GetRecommendationsRequest $request): View
    {
        $user = auth()->user();
        $recommendations = $this->recommendationService->getRecommendations($user, 30);

        return view('jav::dashboard.recommendations', compact('recommendations'));
    }

    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->javNotifications()
            ->with('jav:id,uuid,code,title')
            ->unread()
            ->latest('id')
            ->limit(20)
            ->get();

        return response()->json([
            'count' => $notifications->count(),
            'items' => $notifications,
        ]);
    }

    public function markNotificationRead(Request $request, UserLikeNotification $notification): RedirectResponse|JsonResponse
    {
        abort_unless($notification->user_id === (int) $request->user()->id, 403);

        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    public function markAllNotificationsRead(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->javNotifications()
            ->unread()
            ->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Build a lightweight realtime sync snapshot from queue + cache state.
     *
     * @return array<string, mixed>
     */
    private function buildSyncProgressSnapshot(): array
    {
        $jobsTableExists = Schema::hasTable('jobs');
        $failedJobsTableExists = Schema::hasTable('failed_jobs');

        $pendingJobs = $jobsTableExists
            ? DB::table('jobs')->where('queue', 'jav')->count()
            : 0;

        $failedJobs = $failedJobsTableExists
            ? DB::table('failed_jobs')
                ->where('queue', 'jav')
                ->where('failed_at', '>=', now()->subDay())
                ->count()
            : 0;

        $activeSync = Cache::get('jav:sync:active');

        $phase = 'idle';
        if ($pendingJobs > 0) {
            $phase = 'processing';
        } elseif (is_array($activeSync)) {
            $phase = 'completed';
        }

        $metrics = Cache::get('jav:sync:metrics', []);
        $now = now();
        $currentTs = $now->timestamp;
        $ratePerMinute = null;
        $etaSeconds = null;

        if (($metrics['last_ts'] ?? null) && array_key_exists('last_pending', $metrics)) {
            $elapsed = max(1, $currentTs - (int) $metrics['last_ts']);
            $delta = (int) $metrics['last_pending'] - $pendingJobs;

            if ($delta > 0) {
                $instantRate = ($delta / $elapsed) * 60;
                $previousRate = isset($metrics['rate_per_min']) ? (float) $metrics['rate_per_min'] : $instantRate;
                $ratePerMinute = round(($previousRate * 0.6) + ($instantRate * 0.4), 2);
            } elseif (isset($metrics['rate_per_min'])) {
                $ratePerMinute = (float) $metrics['rate_per_min'];
            }
        }

        if ($ratePerMinute && $ratePerMinute > 0 && $pendingJobs > 0) {
            $etaSeconds = (int) round(($pendingJobs / $ratePerMinute) * 60);
        }

        Cache::put('jav:sync:metrics', [
            'last_pending' => $pendingJobs,
            'last_ts' => $currentTs,
            'rate_per_min' => $ratePerMinute,
        ], now()->addHours(6));

        $recentFailures = $failedJobsTableExists
            ? DB::table('failed_jobs')
                ->where('queue', 'jav')
                ->orderByDesc('failed_at')
                ->limit(5)
                ->get(['id', 'failed_at', 'exception'])
                ->map(static function (object $failure): array {
                    $message = 'Unknown error';
                    if (is_string($failure->exception) && $failure->exception !== '') {
                        $firstLine = explode("\n", $failure->exception)[0] ?? '';
                        $message = mb_strimwidth(trim($firstLine), 0, 180, '...');
                    }

                    return [
                        'id' => $failure->id,
                        'failed_at' => Carbon::parse($failure->failed_at)->toDateTimeString(),
                        'message' => $message,
                    ];
                })
                ->values()
                ->all()
            : [];

        return [
            'phase' => $phase,
            'pending_jobs' => $pendingJobs,
            'failed_jobs_24h' => $failedJobs,
            'throughput_per_min' => $ratePerMinute,
            'eta_seconds' => $etaSeconds,
            'eta_human' => $etaSeconds ? gmdate('H:i:s', $etaSeconds) : null,
            'active_sync' => $activeSync,
            'recent_failures' => $recentFailures,
            'updated_at' => $now->toDateTimeString(),
        ];
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
