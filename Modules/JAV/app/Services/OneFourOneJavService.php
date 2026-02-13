<?php

namespace Modules\JAV\Services;

use Modules\JAV\Dtos\Item;
use Modules\Core\Facades\Config;
use Modules\JAV\Services\Clients\OneFourOneJavClient;
use Modules\JAV\Services\OneFourOneJav\ItemAdapter;
use Modules\JAV\Services\OneFourOneJav\ItemsAdapter;
use Symfony\Component\DomCrawler\Crawler;

class OneFourOneJavService
{
    public function __construct(
        protected OneFourOneJavClient $client
    ) {
    }

    public function new(?int $page = null): ItemsAdapter
    {
        $isAuto = $page === null;
        $page = $page ?? Config::get('onefourone', 'new_page', 1);

        $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get('/new?page=' . $page)]);

        \Modules\JAV\Events\ItemsFetched::dispatch(
            $items->items(),
            '141jav',
            $items->currentPage()
        );

        if ($isAuto && $items->hasNextPage()) {
            Config::set('onefourone', 'new_page', $items->nextPage());
        }

        return $items;
    }

    public function popular(?int $page = null): ItemsAdapter
    {
        $isAuto = $page === null;
        $page = $page ?? Config::get('onefourone', 'popular_page', 1);

        $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get('/popular/?page=' . $page)]);

        \Modules\JAV\Events\ItemsFetched::dispatch(
            $items->items(),
            '141jav',
            $items->currentPage()
        );

        if ($isAuto && $items->hasNextPage()) {
            Config::set('onefourone', 'popular_page', $items->nextPage());
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
