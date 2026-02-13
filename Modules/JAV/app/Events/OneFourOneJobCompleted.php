<?php

namespace Modules\JAV\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OneFourOneJobCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $type,
        public int $itemsCount
    ) {}
}
