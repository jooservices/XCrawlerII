<?php

namespace Modules\JAV\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

    public function index(Request $request)
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
            $view = view('jav::dashboard.partials.movie_card', compact('items'))->render();
            // Since $items is a Paginator, we need to iterate over it OR pass the items to the view properly.
            // The partial expects a single $item. We need to loop.

            // Wait, the partial is for a SINGLE card. I should loop here and concatenate.
            $html = '';
            foreach ($items as $item) {
                $html .= view('jav::dashboard.partials.movie_card', compact('item'))->render();
            }

            return response()->json([
                'html' => $html,
                'next_page_url' => $items->nextPageUrl(),
            ]);
        }

        return view('jav::dashboard.index', compact('items', 'query', 'filters', 'sort', 'direction'));
    }

    public function actors(Request $request)
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
                'next_page_url' => $actors->nextPageUrl(),
            ]);
        }

        return view('jav::dashboard.actors', compact('actors', 'query'));
    }

    public function tags(Request $request)
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
                'next_page_url' => $tags->nextPageUrl(),
            ]);
        }

        return view('jav::dashboard.tags', compact('tags', 'query'));
    }

    public function show(\Modules\JAV\Models\Jav $jav)
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

    public function view(\Modules\JAV\Models\Jav $jav)
    {
        $jav->increment('views');

        return response()->json(['views' => $jav->views]);
    }

    public function download(\Modules\JAV\Models\Jav $jav)
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
            if ($jav->source === 'onejav') {
                $service = app(\Modules\JAV\Services\OnejavService::class);
            } else {
                $service = app(\Modules\JAV\Services\OneFourOneJavService::class);
            }

            $item = $service->item($jav->url);
            \Illuminate\Support\Facades\Log::info('Download requested', ['url' => $jav->url, 'download_link' => $item->download]);

            if (empty($item->download)) {
                return back()->with('error', 'Download link not found.');
            }

            $downloadLink = $item->download;
            if (str_starts_with($downloadLink, '/')) {
                $baseUrl = $jav->source === 'onejav' ? 'https://onejav.com' : 'https://www.141jav.com';
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

    public function request(Request $request)
    {
        $request->validate([
            'source' => 'required|in:onejav,141jav',
            'type' => 'required|in:new,popular',
        ]);

        $command = $request->source === 'onejav' ? 'jav:onejav' : 'jav:141';
        \Illuminate\Support\Facades\Artisan::call($command, ['type' => $request->type]);

        return response()->json(['message' => 'Sync request queued successfully.']);
    }

    public function status()
    {
        return response()->json([
            'onejav' => [
                'new' => \Modules\Core\Facades\Config::get('onejav', 'new_page', 1),
                'popular' => \Modules\Core\Facades\Config::get('onejav', 'popular_page', 1),
            ],
            '141jav' => [
                'new' => \Modules\Core\Facades\Config::get('onefourone', 'new_page', 1),
                'popular' => \Modules\Core\Facades\Config::get('onefourone', 'popular_page', 1),
            ],
        ]);
    }
    public function toggleLike(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'type' => 'required|string|in:jav,actor,tag',
        ]);

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

    public function history(Request $request)
    {
        $user = auth()->user();
        $history = \Modules\JAV\Models\UserJavHistory::with('jav')
            ->where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->paginate(30);

        return view('jav::dashboard.history', compact('history'));
    }

    public function favorites(Request $request)
    {
        $user = auth()->user();
        $favorites = \Modules\JAV\Models\Favorite::with(['favoritable'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return view('jav::dashboard.favorites', compact('favorites'));
    }
    public function recommendations(Request $request)
    {
        $user = auth()->user();
        $recommendations = $this->recommendationService->getRecommendations($user, 30);

        return view('jav::dashboard.recommendations', compact('recommendations'));
    }
}
