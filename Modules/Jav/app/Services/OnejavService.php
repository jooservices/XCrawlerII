<?php

namespace Modules\Jav\app\Services;

use Modules\Core\Services\SettingService;
use Modules\Jav\Jobs\OnejavFetchItems;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class OnejavService
{
    public const string SETTING_GROUP = 'onejav';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function new(): void
    {
        OnejavFetchItems::dispatch(
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
        OnejavFetchItems::dispatch(
            __FUNCTION__,
            $this->getSetting('popular_current_page', 1)
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
