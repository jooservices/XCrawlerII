<?php

declare(strict_types=1);

namespace Modules\Core\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Core\Contracts\Events\ChangeSetBuilderInterface;

/**
 * @method static \Modules\Core\Dto\Events\ChangeSet build(array $previous, array $new, ?array $onlyKeys = null, int $maxKeys = 100, int $maxDepth = 1)
 *
 * @see ChangeSetBuilderInterface
 */
final class ChangeSet extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ChangeSetBuilderInterface::class;
    }
}
