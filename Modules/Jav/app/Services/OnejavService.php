<?php

namespace Modules\Jav\Services;

use Carbon\Carbon;
use Modules\Core\Services\SettingService;
use Modules\Jav\Jobs\OnejavDailyFetchJob;
use Modules\Jav\Jobs\OnejavFetchItemsJob;
use Modules\Jav\Onejav\CrawlingService;
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
