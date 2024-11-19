<?php

namespace Modules\Jav\Services\Onejav;

use Carbon\Carbon;
use Modules\Core\Facades\Setting;
use Modules\Core\Services\SettingService;
use Modules\Jav\Client\Onejav\CrawlingService;
use Modules\Jav\Dto\ItemsDto;
use Modules\Jav\Dto\TagDto;
use Modules\Jav\Events\Onejav\HaveNextPageEvent;
use Modules\Jav\Jobs\Onejav\FetchItemsJob;
use Modules\Jav\Repositories\OnejavRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class OnejavService
{
    public const string ONEJAV_QUEUE_NAME = 'onejav';

    public const string SETTING_GROUP = 'onejav';

    public function __construct(private CrawlingService $service)
    {
    }

    public function crawl(string $endpoint, ?int $page = null): ItemsDto
    {
        if (!$page) {
            $page = Setting::get('onejav', $endpoint . '_current_page', 1);
        }

        $service = app(CrawlingService::class);
        $items = $service->getItems($endpoint, $page);

        /**
         * Saving states
         */
        Setting::set('onejav', $endpoint . '_last_page', $items->getLastPage());

        if ($items->isLastPage()) {
            Setting::set('onejav', $endpoint . '_current_page', 1);
        } else {
            Setting::set('onejav', $endpoint . '_current_page', $items->getPage() + 1);

            HaveNextPageEvent::dispatch($endpoint, $items->getPage() + 1, $items->getLastPage());
        }

        $repository = app(OnejavRepository::class);

        foreach ($items->getItems() as $item) {
            $repository->insert($item->toArray());
        }

        return $items;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function new(): void
    {
        FetchItemsJob::dispatch(__FUNCTION__);
    }

    public function popular(): void
    {
        FetchItemsJob::dispatch(__FUNCTION__);
    }

    public function daily(): void
    {
        FetchItemsJob::dispatch(
            Carbon::now()->format(CrawlingService::DEFAULT_DATE_FORMAT),
            1,
            true
        );
    }

    public function tags(): void
    {
        $this->service->tags()->map(function (TagDto $tag) {
            FetchItemsJob::dispatch('tag/' . $tag->name);
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getSetting(string $key, mixed $default = null)
    {
        return app(SettingService::class)
            ->get(self::SETTING_GROUP, $key, $default);
    }
}
