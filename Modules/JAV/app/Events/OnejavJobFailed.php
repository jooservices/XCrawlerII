<?php

namespace Modules\JAV\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class OnejavJobFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $type,
        public Throwable $exception
    ) {
    }
}
