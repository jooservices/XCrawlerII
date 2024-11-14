<?php

namespace Modules\Jav\Services\Onejav;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Client\Interfaces\IClient;
use Modules\Client\Services\ClientManager;
use Modules\Core\Services\SettingService;
use Modules\Jav\Client\Onejav\Client;
use Modules\Jav\Dto\TagDto;
use Modules\Jav\Entities\OnejavItemEntity;
use Modules\Jav\Events\CrawlingFailedEvent;
use Modules\Jav\Events\OnejavHaveNextPageEvent;
use Modules\Jav\Events\OnejavItemParsedEvent;
use Modules\Jav\Helpers\OnejavHelper;
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

        app(SettingService::class)->set(
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

    public function item(Crawler $element): OnejavItemEntity
    {
        $item = new OnejavItemEntity();

        if ($element->filter('h5.title a')->count()) {
            $item->url = trim($element->filter('h5.title a')->attr('href'));
        }

        if ($element->filter('.columns img.image')->count()) {
            $item->cover = trim($element->filter('.columns img.image')->attr('src'));
        }

        if ($element->filter('h5 a')->count()) {
            $item->dvd_id = trim($element->filter('h5 a')->text(null, false));
            /**
             * @TODO Use Helper
             */
            $item->dvd_id = OnejavHelper::parseDvdId($item->dvd_id);
        }

        if ($element->filter('h5 span')->count()) {
            $item->size = trim($element->filter('h5 span')->text(null, false));
            $item->size = OnejavHelper::convertSize($item->size);
        }

        // Always use href because it'll never change but text will be
        $item->date = OnejavHelper::convertToDate(trim($element->filter('.subtitle.is-6 a')->attr('href')));
        $item->genres = collect($element->filter('.tags .tag')->each(
            function ($genres) {
                return trim($genres->text(null, false));
            }
        ))->reject(function ($value) {
            return empty($value);
        })->unique()->toArray();

        // Description
        $description = $element->filter('.level.has-text-grey-dark');
        $item->description = $description->count() ? trim($description->text(null, false)) : null;
        $item->description = preg_replace("/\r|\n/", '', $item->description);

        $item->performers = collect($element->filter('.panel .panel-block')->each(
            function ($performers) {
                return trim($performers->text(null, false));
            }
        ))->reject(function ($value) {
            return empty($value);
        })->unique()->toArray();

        $item->torrent = trim($element->filter('.control.is-expanded a')->attr('href'));

        // Gallery. Only for FC
        $gallery = $element->filter('.columns .column a img');
        if ($gallery->count()) {
            $item->gallery = collect($gallery->each(
                function ($image) {
                    return trim($image->attr('src'));
                }
            ))->reject(function ($value) {
                return empty($value);
            })->unique()->toArray();
        }

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
