<?php

namespace Modules\Core\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\Services\ConfigService;

/**
 * @method static mixed get(string $group, string $key, mixed $default = null)
 * @method static \Modules\Core\Models\Config set(string $group, string $key, mixed $value, ?string $description = null)
 *
 * @see \Modules\Core\Services\ConfigService
 */
class Config extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ConfigService::class;
    }
}
