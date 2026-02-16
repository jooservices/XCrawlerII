<?php

namespace Modules\JAV\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JavStoreFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ?string $code,
        public string $source,
        public string $error
    ) {}
}
