<?php

namespace Modules\JAV\Services;

use Modules\Core\Facades\Config;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Services\Clients\FfjavClient;
use Modules\JAV\Services\Ffjav\ItemAdapter;
use Modules\JAV\Services\Ffjav\ItemsAdapter;
use Symfony\Component\DomCrawler\Crawler;

class FfjavService
{
    public function __construct(
        protected FfjavClient $client
    ) {}

    public function new(?int $page = null): ItemsAdapter
    {
        $isAuto = $page === null;
        $page = $page ?? Config::get('ffjav', 'new_page', 1);

        $path = $page === 1 ? '/javtorrent' : '/javtorrent/page/'.$page;
        $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get($path)]);

        \Modules\JAV\Events\ItemsFetched::dispatch(
            $items->items(),
            'ffjav',
            $items->currentPage()
        );

        if ($isAuto && $items->hasNextPage()) {
            Config::set('ffjav', 'new_page', $items->nextPage());
        }

        return $items;
    }

    public function popular(?int $page = null): ItemsAdapter
    {
        $isAuto = $page === null;
        $page = $page ?? Config::get('ffjav', 'popular_page', 1);

        $path = $page === 1 ? '/popular' : '/popular/page/'.$page;
        $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get($path)]);

        \Modules\JAV\Events\ItemsFetched::dispatch(
            $items->items(),
            'ffjav',
            $items->currentPage()
        );

        if ($isAuto && $items->hasNextPage()) {
            Config::set('ffjav', 'popular_page', $items->nextPage());
        }

        return $items;
    }

    public function item(string $url): Item
    {
        $response = $this->client->get($url);
        $crawler = new Crawler($response->toPsrResponse()->getBody()->getContents());

        return (new ItemAdapter($crawler))->getItem();
    }
}
