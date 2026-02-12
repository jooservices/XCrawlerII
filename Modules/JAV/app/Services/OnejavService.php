<?php

namespace Modules\JAV\Services;

use Modules\JAV\Dtos\Item;
use Modules\JAV\Services\Clients\OnejavClient;
use Modules\JAV\Services\Onejav\ItemAdapter;
use Modules\JAV\Services\Onejav\ItemsAdapter;
use Symfony\Component\DomCrawler\Crawler;

class OnejavService
{
    public function __construct(
        protected OnejavClient $client
    ) {
    }

    public function new(int $page = 1): ItemsAdapter
    {
        return app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get('/new?page=' . $page)]);
    }

    public function popular(int $page = 1): ItemsAdapter
    {
        return app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get('/popular/?page=' . $page)]);
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
