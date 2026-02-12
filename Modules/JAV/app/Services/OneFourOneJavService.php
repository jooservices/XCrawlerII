<?php

namespace Modules\JAV\Services;

use Modules\JAV\Dtos\Item;
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
