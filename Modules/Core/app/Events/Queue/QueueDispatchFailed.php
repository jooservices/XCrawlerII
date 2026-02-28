<?php

declare(strict_types=1);

namespace Modules\Core\Events\Queue;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class QueueDispatchFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $jobClass,
        public readonly string $queueName,
        public readonly ?Throwable $exception = null
    ) {
    }

    /**
     * Safe exception summary for payload (message + class, no sensitive stack).
     */
    public function getExceptionSummary(): ?array
    {
        if ($this->exception === null) {
            return null;
        }

        return [
            'message' => $this->exception->getMessage(),
            'class' => $this->exception::class,
        ];
    }
}
