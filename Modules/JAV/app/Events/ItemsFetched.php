<?php

namespace Modules\JAV\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\JAV\Dtos\Items;

class ItemsFetched
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Items $items,
        public string $source,
        public int $currentPage
    ) {
    }
}
