<?php

namespace Modules\JAV\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RelatedCacheWarmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $source,
        public string $kind,
        public int $javId,
        public int $limit,
        public string $cacheKey,
        public int $resultCount,
        public int $ttlSeconds
    ) {}
}
