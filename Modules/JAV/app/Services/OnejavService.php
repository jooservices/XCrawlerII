<?php

namespace Modules\JAV\Services;

use Modules\Core\Facades\Config;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Services\Clients\OnejavClient;
use Modules\JAV\Services\Onejav\ItemAdapter;
use Modules\JAV\Services\Onejav\ItemsAdapter;
use Symfony\Component\DomCrawler\Crawler;

class OnejavService
{
    public function __construct(
        protected OnejavClient $client
    ) {}

    public function new(?int $page = null): ItemsAdapter
    {
        $isAuto = $page === null;
        $page = $page ?? Config::get('onejav', 'new_page', 1);

        $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get('/new?page='.$page)]);

        \Modules\JAV\Events\ItemsFetched::dispatch(
            $items->items(),
            'onejav',
            $items->currentPage()
        );

        if ($isAuto && $items->hasNextPage()) {
            Config::set('onejav', 'new_page', $items->nextPage());
        }

        return $items;
    }

    public function popular(?int $page = null): ItemsAdapter
    {
        $isAuto = $page === null;
        $page = $page ?? Config::get('onejav', 'popular_page', 1);

        $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get('/popular/?page='.$page)]);

        \Modules\JAV\Events\ItemsFetched::dispatch(
            $items->items(),
            'onejav',
            $items->currentPage()
        );

        if ($isAuto && $items->hasNextPage()) {
            Config::set('onejav', 'popular_page', $items->nextPage());
        }

        return $items;
    }

    public function tags()
    {
        return $this->client->get('/tag');
    }

    public function item(string $url): Item
    {
        $response = $this->client->get($url);
        $crawler = new Crawler($response->toPsrResponse()->getBody()->getContents());

        return (new ItemAdapter($crawler))->getItem();
    }
}
