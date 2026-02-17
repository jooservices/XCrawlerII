<?php

namespace Modules\JAV\Console;

use Illuminate\Console\Command;
use Modules\JAV\Services\CrawlerResponseCacheService;

class JavCachePruneCommand extends Command
{
    protected $signature = 'jav:cache:prune';

    protected $description = 'Prune expired crawler response cache entries.';

    public function handle(CrawlerResponseCacheService $cacheService): int
    {
        $deleted = $cacheService->pruneExpired();

        $this->info("Pruned {$deleted} expired cache entries.");

        return self::SUCCESS;
    }
}
