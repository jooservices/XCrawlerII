<?php

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Modules\Core\Services\AnalyticsFlushService;

/**
 * CLI entrypoint to flush Redis analytics counters into persistent rollups.
 */
class FlushAnalyticsCommand extends Command
{
    protected $signature = 'analytics:flush';

    protected $description = 'Flush Redis analytics counters to Mongo and MySQL';

    public function handle(AnalyticsFlushService $flushService): int
    {
        $result = $flushService->flush();

        $this->info(sprintf('Flushed %d keys, %d errors.', $result['keys_processed'] ?? 0, $result['errors'] ?? 0));

        return (($result['errors'] ?? 0) > 0) ? self::FAILURE : self::SUCCESS;
    }
}
