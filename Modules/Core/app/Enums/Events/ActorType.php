<?php

declare(strict_types=1);

namespace Modules\Core\Enums\Events;

enum ActorType: string
{
    case User = 'user';
    case System = 'system';
    case Api = 'api';
    case Console = 'console';
    case Unknown = 'unknown';
}
