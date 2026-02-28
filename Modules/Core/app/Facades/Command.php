<?php

declare(strict_types=1);

namespace Modules\Core\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\Services\CommandService;

/**
 * @method static void schedule()
 * @method static void command(string $command, array $params = [])
 *
 * @see \Modules\Core\Services\CommandService
 */
class Command extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CommandService::class;
    }
}
