<?php

namespace Modules\JAV\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JavStored
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $javId,
        public string $code,
        public string $source,
        public int $actorsCount,
        public int $tagsCount,
        public bool $created,
    ) {}
}
