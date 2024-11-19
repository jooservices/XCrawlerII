<?php

namespace Modules\Jav\Client\Onejav;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Client\Interfaces\IClient;
use Modules\Client\Services\ClientManager;
use Modules\Core\Facades\Setting;
use Modules\Jav\Dto\ItemDto;
use Modules\Jav\Dto\TagDto;
use Modules\Jav\Events\CrawlingFailedEvent;
use Modules\Jav\Events\OnejavHaveNextPageEvent;
use Modules\Jav\Events\OnejavItemParsedEvent;
use Modules\Jav\Services\Onejav\OnejavService;
use Symfony\Component\DomCrawler\Crawler;

class CrawlingService
{
    private IClient $client;

    public const string DEFAULT_DATE_FORMAT = 'Y/m/d';

    public function __construct(private readonly ClientManager $clientManager)
    {
        $this->client = $this
            ->clientManager
            ->getClient(Client::class);
    }

    public function getItems(string $endpoint = 'new', int $page = 1): Collection
    {
        $response = $this->client->get($endpoint, ['page' => $page]);

        if (!$response->isSuccess()) {
            CrawlingFailedEvent::dispatch($response);

            return collect();
        }

        $pageNode = $response->parseBody()->getData()->filter('a.pagination-link')->last();
        $lastPage = $pageNode->count() === 0 ? 1 : (int) $pageNode->text();

        Setting::set(
            OnejavService::SETTING_GROUP,
            Str::slug($endpoint, '_') . '_last_page',
            $lastPage
        );

        if ($page < $lastPage) {
            OnejavHaveNextPageEvent::dispatch($endpoint, $page, $lastPage);
        }

        return collect($response->parseBody()->getData()->filter('.container .columns')->each(function ($el) {
            return $this->item($el);
        }));
    }

    public function item(Crawler $element): ItemDto
    {
        $item = (new ItemDto())->transform($element);

        Event::dispatch(new OnejavItemParsedEvent($item));

        return $item;
    }

    public function tags(): Collection
    {
        $response = $this->client->get('tag');
        if (!$response->isSuccess()) {
            CrawlingFailedEvent::dispatch($response);

            return collect();
        }

        $element = $response->parseBody()->getData();

        return collect($element->filter('.columns .column a')->each(function (Crawler $el) {
            $tagDto = new TagDto();
            $tagDto->name = trim($el->text());
            $tagDto->slug = Str::slug($tagDto->name);
            $tagDto->link = $el->attr('href');

            return $tagDto;
        }));
    }
}
