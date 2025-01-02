<?php

namespace Modules\Jav\Client\MissAv;

use JsonException;
use Modules\Client\Interfaces\IClient;
use Modules\Client\Services\ClientManager;
use Modules\Jav\Dto\MissAv\ItemDetailDto;
use Modules\Jav\Dto\MissAv\ItemDto;
use Modules\Jav\Dto\MissAv\ItemsDto;
use Symfony\Component\DomCrawler\Crawler;

final readonly class CrawlingService
{
    private IClient $client;

    public function __construct(private ClientManager $clientManager)
    {
        $this->client = $this
            ->clientManager
            ->getClient(Client::class);
    }

    /**
     * @throws JsonException
     */
    public function getItems(string $endpoint = 'new', int $page = 1): ?ItemsDto
    {
        $response = $this->client->get($endpoint, ['page' => $page]);

        if (!$response->isSuccess()) {
            return null;
        }

        $items = $response->parseBody()->getData()->filter('.thumbnail.group');

        $items = $items->each(function ($element) {
            return $this->item($element);
        });

        $lastPage = (int) $response->parseBody()->getData()
            ->filter('a.relative.inline-flex.items-center.px-4')
            ->last()
            ->text();

        return (new ItemsDto())->transform([
            'items' => collect($items),
            'page' => $page,
            'last_page' => $lastPage,
        ]);
    }

    public function item(Crawler $element): ItemDto
    {
        return (new ItemDto())->transform($element);
    }

    /**
     * @throws JsonException
     */
    public function itemDetail(string $url): ?ItemDetailDto
    {
        $response = $this->client->get($url);

        if (!$response->isSuccess()) {
            return null;
        }

        return (new ItemDetailDto())->transform($response->parseBody()->getData());
    }
}
