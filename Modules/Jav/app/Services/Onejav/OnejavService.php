<?php

namespace Modules\Jav\Services\Onejav;

use Carbon\Carbon;
use Modules\Core\Services\SettingService;
use Modules\Jav\Client\Onejav\CrawlingService;
use Modules\Jav\Dto\TagDto;
use Modules\Jav\Jobs\OnejavDailyFetchJob;
use Modules\Jav\Jobs\OnejavFetchItemsJob;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class OnejavService
{
    public const string ONEJAV_QUEUE_NAME = 'onejav';

    public const string SETTING_GROUP = 'onejav';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function new(): void
    {
        OnejavFetchItemsJob::dispatch(
            __FUNCTION__,
            $this->getSetting('new_current_page', 1)
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function popular(): void
    {
        OnejavFetchItemsJob::dispatch(
            __FUNCTION__,
            $this->getSetting('popular_current_page', 1)
        );
    }

    public function daily(): void
    {
        OnejavDailyFetchJob::dispatch(
            Carbon::now()->format(CrawlingService::DEFAULT_DATE_FORMAT)
        );
    }

    public function tags(): void
    {
        app(CrawlingService::class)->tags()->map(function (TagDto $tag) {
            OnejavFetchItemsJob::dispatch(
                'tag/' . $tag->name,
                $this->getSetting($tag->name . '_current_page', 1)
            );
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
