<?php

declare(strict_types=1);

namespace Modules\Core\Events\Queue;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueDispatched
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $jobClass,
        public readonly string $queueName
    ) {
    }
}
