<?php

namespace Modules\JAV\Services;

use Carbon\Carbon;
use Modules\JAV\Models\Tag;
use Modules\Core\Facades\Config;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Services\Clients\OnejavClient;
use Modules\JAV\Services\Onejav\ItemAdapter;
use Modules\JAV\Services\Onejav\ItemsAdapter;
use Modules\JAV\Services\Onejav\TagsAdapter;
use Symfony\Component\DomCrawler\Crawler;

class OnejavService
{
    public function __construct(
        protected OnejavClient $client
    ) {
    }

    public function new(?int $page = null): ItemsAdapter
    {
        $isAutoMode = $page === null;
        $page = $page ?? Config::get('onejav', 'new_page', 1);

        $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get('/new?page=' . $page)]);

        \Modules\JAV\Events\ItemsFetched::dispatch(
            $items->items(),
            'onejav',
            $items->currentPage()
        );

        if ($isAutoMode) {
            Config::set('onejav', 'new_page', $items->nextPage());
        }

        return $items;
    }

    public function popular(?int $page = null): ItemsAdapter
    {
        $isAutoMode = $page === null;
        $page = $page ?? Config::get('onejav', 'popular_page', 1);

        $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get('/popular/?page=' . $page)]);

        \Modules\JAV\Events\ItemsFetched::dispatch(
            $items->items(),
            'onejav',
            $items->currentPage()
        );

        if ($isAutoMode) {
            Config::set('onejav', 'popular_page', $items->nextPage());
        }

        return $items;
    }

    public function daily(?string $date = null, ?int $page = null): ItemsAdapter
    {
        $date = $date
            ? Carbon::parse($date)->format('Y/m/d')
            : Carbon::now()->format('Y/m/d');

        $path = '/' . $date;
        if (($page ?? 1) > 1) {
            $path .= '?page=' . $page;
        }

        $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get($path)]);

        \Modules\JAV\Events\ItemsFetched::dispatch(
            $items->items(),
            'onejav',
            $items->currentPage()
        );

        return $items;
    }

    public function tags(): \Illuminate\Support\Collection
    {
        $response = $this->client->get('/tag');
        $tags = (new TagsAdapter($response))->tags();

        $tags->each(function (string $name) {
            Tag::firstOrCreate(['name' => $name]);
        });

        return $tags;
    }

    public function item(string $url): Item
    {
        $response = $this->client->get($url);
        $crawler = new Crawler($response->toPsrResponse()->getBody()->getContents());

        return (new ItemAdapter($crawler))->getItem();
    }
}
