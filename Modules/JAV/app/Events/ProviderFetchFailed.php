<?php

namespace Modules\JAV\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProviderFetchFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $source,
        public string $type,
        public string $path,
        public ?int $page,
        public string $error,
        public int $durationMs
    ) {}
}
