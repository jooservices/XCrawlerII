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

    public function __construct(
        private readonly CrawlingService $service,
        private readonly OnejavRepository $repository
    ) {
    }

    final public function crawl(string $endpoint, ?int $page = null): ItemsDto
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

        foreach ($items->getItems() as $item) {
            $this->repository->insert($item->toArray());
        }

        return $items;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    final public function new(): void
    {
        FetchItemsJob::dispatch(__FUNCTION__);
    }

    final public function popular(): void
    {
        FetchItemsJob::dispatch(__FUNCTION__);
    }

    final public function daily(): void
    {
        $date = Carbon::now()->format(CrawlingService::DEFAULT_DATE_FORMAT);
        /**
         * Daily will be loop until finished
         */
        FetchItemsJob::dispatch($date, 1, true);
    }

    final public function tags(): void
    {
        $this->service->tags()->map(function (TagDto $tag) {
            /**
             * Tag will be loop until finished
             */
            FetchItemsJob::dispatch('tag/' . $tag->name, 1, true);
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    final public function getSetting(string $key, mixed $default = null): mixed
    {
        return app(SettingService::class)
            ->get(self::SETTING_GROUP, $key, $default);
    }
}
