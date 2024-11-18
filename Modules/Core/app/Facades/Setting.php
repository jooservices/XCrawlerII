<?php

namespace Modules\Core\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\Services\SettingService;

class Setting extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return SettingService::class;
    }
}
