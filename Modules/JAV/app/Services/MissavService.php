<?php

namespace Modules\JAV\Services;

use Modules\Core\Facades\Config;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Events\ProviderFetchCompleted;
use Modules\JAV\Events\ProviderFetchFailed;
use Modules\JAV\Events\ProviderFetchStarted;
use Modules\JAV\Services\Clients\MissavBrowserClient;
use Modules\JAV\Services\Missav\ItemAdapter;
use Modules\JAV\Services\Missav\ItemsAdapter;
use Modules\JAV\Models\MissavSchedule;
use Symfony\Component\DomCrawler\Crawler;

class MissavService
{
    public function __construct(
        protected MissavBrowserClient $browser
    ) {}

    public function new(?int $page = null): ItemsAdapter
    {
        $isAutoMode = $page === null;
        $page = $page ?? Config::get('missav', 'new_page', 1);
        $path = $this->buildReleasePath($page);
        $startedAt = microtime(true);

        ProviderFetchStarted::dispatch('missav', 'new', $path, $page);

        try {
            $html = $this->fetchAndStoreHtml($path);
            $items = new ItemsAdapter($html);
            $itemsDto = $items->items();

            ProviderFetchCompleted::dispatch(
                'missav',
                'new',
                $path,
                $page,
                $items->currentPage(),
                $itemsDto->items->count(),
                $items->nextPage(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            \Modules\JAV\Events\ItemsFetched::dispatch(
                $itemsDto,
                'missav',
                $items->currentPage()
            );

            $this->enqueueItems($itemsDto->items);

            if ($isAutoMode) {
                Config::set('missav', 'new_page', $items->nextPage());
            }

            return $items;
        } catch (\Throwable $exception) {
            ProviderFetchFailed::dispatch(
                'missav',
                'new',
                $path,
                $page,
                $exception->getMessage(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            throw $exception;
        }
    }

    public function popular(?int $page = null): ItemsAdapter
    {
        return $this->emptyItems();
    }

    public function daily(?string $date = null, ?int $page = null): ItemsAdapter
    {
        return $this->emptyItems();
    }

    public function tags(): \Illuminate\Support\Collection
    {
        return collect();
    }

    public function item(string $url): Item
    {
        $html = $this->fetchAndStoreHtml($url);
        $crawler = new Crawler($html);

        return (new ItemAdapter($crawler))->getItem();
    }

    private function buildReleasePath(int $page, ?string $sort = null): string
    {
        $path = '/dm590/en/release';
        $query = [];

        if ($sort !== null) {
            $query['sort'] = $sort;
        }

        if ($page > 1) {
            $query['page'] = $page;
        }

        if ($query !== []) {
            $path .= '?'.http_build_query($query);
        }

        return $path;
    }

    private function emptyItems(): ItemsAdapter
    {
        return new ItemsAdapter(null);
    }

    private function fetchAndStoreHtml(string $url): string
    {
        $html = $this->browser->fetchHtml($url);
        $path = $this->storeHtml($html);

        try {
            return (string) file_get_contents($path);
        } finally {
            @unlink($path);
        }
    }

    private function storeHtml(string $html): string
    {
        $relativeDir = (string) config('jav.missav.tmp_dir', 'app/tmp/missav');
        $dir = storage_path($relativeDir);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = sprintf('missav_%s_%s.html', date('Ymd_His'), bin2hex(random_bytes(4)));
        $path = rtrim($dir, '/').'/'.$filename;
        file_put_contents($path, $html);

        return $path;
    }

    private function enqueueItems(\Illuminate\Support\Collection $items): void
    {
        foreach ($items as $item) {
            if ($item->url === null || $item->url === '') {
                continue;
            }

            MissavSchedule::firstOrCreate(
                ['url' => $item->url],
                [
                    'item_id' => $item->id,
                    'code' => $item->code,
                    'title' => $item->title,
                    'status' => 'pending',
                ]
            );
        }
    }
}
