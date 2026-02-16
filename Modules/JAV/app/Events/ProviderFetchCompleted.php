<?php

namespace Modules\JAV\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProviderFetchCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $source,
        public string $type,
        public string $path,
        public ?int $page,
        public int $currentPage,
        public int $itemsCount,
        public int $nextPage,
        public int $durationMs
    ) {}
}
