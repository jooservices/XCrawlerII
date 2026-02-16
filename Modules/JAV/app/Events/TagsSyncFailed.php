<?php

namespace Modules\JAV\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TagsSyncFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $source,
        public string $error,
        public int $durationMs
    ) {}
}
