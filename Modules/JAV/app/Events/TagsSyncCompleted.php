<?php

namespace Modules\JAV\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TagsSyncCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $source,
        public int $totalTags,
        public int $insertedTags,
        public int $durationMs
    ) {}
}
