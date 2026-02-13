<?php

namespace Modules\JAV\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\JAV\Services\SearchService;

class DashboardController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function index(Request $request)
    {
        $query = $request->input('q', '');
        $filters = [
            'actor' => $request->input('actor'),
            'tag' => $request->input('tag'),
        ];

        $items = $this->searchService->searchJav($query, $filters);
        $showCover = filter_var(env('SHOW_COVER', true), FILTER_VALIDATE_BOOLEAN);

        return view('jav::dashboard.index', compact('items', 'query', 'filters', 'showCover'));
    }

    public function actors(Request $request)
    {
        $query = $request->input('q', '');
        $actors = $this->searchService->searchActors($query);

        return view('jav::dashboard.actors', compact('actors', 'query'));
    }

    public function tags(Request $request)
    {
        $query = $request->input('q', '');
        $tags = $this->searchService->searchTags($query);

        return view('jav::dashboard.tags', compact('tags', 'query'));
    }

    public function download(\Modules\JAV\Models\Jav $jav)
    {
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
}
