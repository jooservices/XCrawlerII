<?php

namespace Modules\JAV\Services;

use Carbon\Carbon;
use Modules\Core\Facades\Config;
use Modules\JAV\Dtos\Item;
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
        $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get($path)]);

        \Modules\JAV\Events\ItemsFetched::dispatch(
            $items->items(),
            'ffjav',
            $items->currentPage()
        );

        Config::set('ffjav', 'new_page', $items->nextPage());

        return $items;
    }

    public function popular(?int $page = null): ItemsAdapter
    {
        $page = $page ?? Config::get('ffjav', 'popular_page', 1);

        $path = $page === 1 ? '/popular' : '/popular/page/'.$page;
        $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get($path)]);

        \Modules\JAV\Events\ItemsFetched::dispatch(
            $items->items(),
            'ffjav',
            $items->currentPage()
        );

        Config::set('ffjav', 'popular_page', $items->nextPage());

        return $items;
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

        $items = app()->makeWith(ItemsAdapter::class, ['response' => $this->client->get($path)]);

        \Modules\JAV\Events\ItemsFetched::dispatch(
            $items->items(),
            'ffjav',
            $items->currentPage()
        );

        return $items;
    }

    public function item(string $url): Item
    {
        $response = $this->client->get($url);
        $crawler = new Crawler($response->toPsrResponse()->getBody()->getContents());

        return (new ItemAdapter($crawler))->getItem();
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
}
