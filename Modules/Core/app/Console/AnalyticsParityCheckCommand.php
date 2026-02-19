<?php

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Modules\Core\Services\AnalyticsParityService;

class AnalyticsParityCheckCommand extends Command
{
    protected $signature = 'analytics:parity-check {--limit=100}';

    protected $description = 'Compare MySQL and Mongo analytics counters for JAV movies';

    public function handle(AnalyticsParityService $parityService): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $result = $parityService->check($limit);

        foreach ($result['rows'] as $row) {
            $this->warn(sprintf(
                '%s: MySQL(%d/%d) vs Mongo(%d/%d)',
                $row['code'],
                $row['mysql_views'],
                $row['mysql_downloads'],
                $row['mongo_views'],
                $row['mongo_downloads']
            ));
        }

        $this->info(sprintf('Checked: %d, Mismatches: %d', $result['checked'], $result['mismatches']));

        return $result['mismatches'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
