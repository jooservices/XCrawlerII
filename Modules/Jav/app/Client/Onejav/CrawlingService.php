<?php

namespace Modules\Jav\Client\Onejav;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Client\Interfaces\IClient;
use Modules\Client\Services\ClientManager;
use Modules\Core\Facades\Setting;
use Modules\Jav\Dto\ItemDto;
use Modules\Jav\Dto\ItemsDto;
use Modules\Jav\Dto\TagDto;
use Modules\Jav\Events\CrawlingFailedEvent;
use Modules\Jav\Events\Onejav\HaveNextPageEvent;
use Modules\Jav\Events\Onejav\ItemParsedEvent;
use Modules\Jav\Services\Onejav\OnejavService;
use Symfony\Component\DomCrawler\Crawler;

final readonly class CrawlingService
{
    private IClient $client;

    public const string DEFAULT_DATE_FORMAT = 'Y/m/d';

    public function __construct(private ClientManager $clientManager)
    {
        $this->client = $this
            ->clientManager
            ->getClient(Client::class);
    }

    public function getItems(string $endpoint = 'new', int $page = 1): ItemsDto
    {
        $response = $this->client->get($endpoint, ['page' => $page]);

        if (!$response->isSuccess()) {
            CrawlingFailedEvent::dispatch($response);

            return (new ItemsDto())->transform([
                'items' => collect(),
                'page' => $page,
                'last_page' => 1,
            ]);
        }

        $pageNode = $response->parseBody()->getData()->filter('a.pagination-link')->last();
        $lastPage = $pageNode->count() === 0 ? 1 : (int) $pageNode->text();

        Setting::set(OnejavService::SETTING_GROUP, $endpoint . '_last_page', $lastPage);

        if ($page < $lastPage) {
            HaveNextPageEvent::dispatch($endpoint, $page, $lastPage);
        }

        $items = collect($response->parseBody()->getData()->filter('.container .columns')->each(function ($el) {
            return $this->item($el);
        }));

        return (new ItemsDto())->transform([
            'items' => $items,
            'page' => $page,
            'last_page' => $lastPage,
        ]);
    }

    public function item(Crawler $element): ItemDto
    {
        $item = (new ItemDto())->transform($element);

        Event::dispatch(new ItemParsedEvent($item));

        return $item;
    }

    public function tags(): Collection
    {
        $response = $this->client->get('tag');

        if (!$response->isSuccess()) {
            CrawlingFailedEvent::dispatch($response);

            return collect();
        }

        return collect($response->parseBody()->getData()->filter('.columns .column a')
            ->each(function (Crawler $el) {
                $tagDto = new TagDto();
                $tagDto->name = trim($el->text());
                $tagDto->slug = Str::slug($tagDto->name);
                $tagDto->link = $el->attr('href');

                return $tagDto;
            }));
    }
}
