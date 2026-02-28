<?php

declare(strict_types=1);

namespace Modules\Core\Events\Command;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommandCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $command,
        public readonly int $exitCode,
        public readonly int $durationMs
    ) {}
}
