<?php

declare(strict_types=1);

namespace Modules\Core\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\Services\QueueService;

/**
 * @method static void queue(string $queueClass, array $params = [])
 *
 * @see \Modules\Core\Services\QueueService
 */
class QueueManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return QueueService::class;
    }
}
