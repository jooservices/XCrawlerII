<?php

namespace Modules\JAV\Services;

use Carbon\Carbon;
use Modules\Core\Facades\Config;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Events\ProviderFetchCompleted;
use Modules\JAV\Events\ProviderFetchFailed;
use Modules\JAV\Events\ProviderFetchStarted;
use Modules\JAV\Events\TagsSyncCompleted;
use Modules\JAV\Events\TagsSyncFailed;
use Modules\JAV\Models\Tag;
use Modules\JAV\Services\Clients\FfjavClient;
use Modules\JAV\Services\Ffjav\ItemAdapter;
use Modules\JAV\Services\Ffjav\ItemsAdapter;
use Modules\JAV\Services\Ffjav\TagsAdapter;
use Symfony\Component\DomCrawler\Crawler;

class FfjavService
{
    public function __construct(
        protected FfjavClient $client
    ) {}

    public function new(?int $page = null): ItemsAdapter
    {
        $page = $page ?? Config::get('ffjav', 'new_page', 1);

        $path = $page === 1 ? '/javtorrent' : '/javtorrent/page/'.$page;
        $startedAt = microtime(true);
        ProviderFetchStarted::dispatch('ffjav', 'new', $path, $page);

        try {
            $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get($path)]);
            $itemsDto = $items->items();

            ProviderFetchCompleted::dispatch(
                'ffjav',
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
                'ffjav',
                $items->currentPage()
            );

            Config::set('ffjav', 'new_page', $items->nextPage());

            return $items;
        } catch (\Throwable $exception) {
            ProviderFetchFailed::dispatch(
                'ffjav',
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
        $page = $page ?? Config::get('ffjav', 'popular_page', 1);

        $path = $page === 1 ? '/popular' : '/popular/page/'.$page;
        $startedAt = microtime(true);
        ProviderFetchStarted::dispatch('ffjav', 'popular', $path, $page);

        try {
            $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get($path)]);
            $itemsDto = $items->items();

            ProviderFetchCompleted::dispatch(
                'ffjav',
                'popular',
                $path,
                $page,
                $items->currentPage(),
                $itemsDto->items->count(),
                $items->nextPage(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            \Modules\JAV\Events\ItemsFetched::dispatch(
                $itemsDto,
                'ffjav',
                $items->currentPage()
            );

            Config::set('ffjav', 'popular_page', $items->nextPage());

            return $items;
        } catch (\Throwable $exception) {
            ProviderFetchFailed::dispatch(
                'ffjav',
                'popular',
                $path,
                $page,
                $exception->getMessage(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            throw $exception;
        }
    }

    public function daily(?string $date = null, ?int $page = null): ItemsAdapter
    {
        $date = $date
            ? Carbon::parse($date)->format('Y/m/d')
            : Carbon::now()->format('Y/m/d');

        $path = '/'.$date;
        if (($page ?? 1) > 1) {
            $path .= '/page/'.$page;
        }
        $effectivePage = $page ?? 1;
        $startedAt = microtime(true);
        ProviderFetchStarted::dispatch('ffjav', 'daily', $path, $effectivePage);

        try {
            $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get($path)]);
            $itemsDto = $items->items();

            ProviderFetchCompleted::dispatch(
                'ffjav',
                'daily',
                $path,
                $effectivePage,
                $items->currentPage(),
                $itemsDto->items->count(),
                $items->nextPage(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            \Modules\JAV\Events\ItemsFetched::dispatch(
                $itemsDto,
                'ffjav',
                $items->currentPage()
            );

            return $items;
        } catch (\Throwable $exception) {
            ProviderFetchFailed::dispatch(
                'ffjav',
                'daily',
                $path,
                $effectivePage,
                $exception->getMessage(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            throw $exception;
        }
    }

    public function item(string $url): Item
    {
        $response = $this->client->get($url);
        $crawler = new Crawler($response->toPsrResponse()->getBody()->getContents());

        return (new ItemAdapter($crawler))->getItem();
    }

    public function tags(): \Illuminate\Support\Collection
    {
        $startedAt = microtime(true);

        try {
            $response = $this->client->get('/tag');
            $tags = (new TagsAdapter($response))->tags();

            $tagNames = $tags
                ->map(fn ($name) => trim($name))
                ->filter(fn ($name) => $name !== '')
                ->unique()
                ->values();
            $existingTags = Tag::whereIn('name', $tagNames)->pluck('id', 'name');
            $missingTags = $tagNames->diff($existingTags->keys());
            if ($missingTags->isNotEmpty()) {
                $toInsert = $missingTags->map(fn ($name) => ['name' => $name])->all();
                Tag::insertOrIgnore($toInsert);
            }

            TagsSyncCompleted::dispatch(
                'ffjav',
                $tagNames->count(),
                $missingTags->count(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            return $tags;
        } catch (\Throwable $exception) {
            TagsSyncFailed::dispatch(
                'ffjav',
                $exception->getMessage(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            throw $exception;
        }
    }
}
