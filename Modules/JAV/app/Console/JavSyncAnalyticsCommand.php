<?php

namespace Modules\JAV\Console;

use Illuminate\Console\Command;
use Modules\JAV\Services\AnalyticsSnapshotService;

class JavSyncAnalyticsCommand extends Command
{
    protected $signature = 'jav:sync:analytics
                            {--days=* : Windows to sync (supports 7,14,30,90)}';

    protected $description = 'Sync analytics snapshots from MySQL into MongoDB.';

    public function handle(AnalyticsSnapshotService $snapshotService): int
    {
        $daysOption = $this->option('days');
        $daysList = empty($daysOption)
            ? [7, 14, 30, 90]
            : array_map(static fn ($value): int => (int) $value, (array) $daysOption);

        $daysList = array_values(array_unique(array_filter($daysList, static function (int $days): bool {
            return in_array($days, [7, 14, 30, 90], true);
        })));

        if ($daysList === []) {
            $this->error('No valid day windows provided. Allowed: 7, 14, 30, 90');

            return self::INVALID;
        }

        foreach ($daysList as $days) {
            try {
                $payload = $snapshotService->getSnapshot($days, true, false);
                $this->info(sprintf(
                    'Synced Mongo analytics snapshot for %d days (movies=%d, actors=%d, tags=%d).',
                    $days,
                    (int) ($payload['totals']['jav'] ?? 0),
                    (int) ($payload['totals']['actors'] ?? 0),
                    (int) ($payload['totals']['tags'] ?? 0),
                ));
            } catch (\Throwable $exception) {
                $this->error(sprintf(
                    'Failed syncing %d-day analytics snapshot to MongoDB: %s',
                    $days,
                    $exception->getMessage()
                ));

                return self::FAILURE;
            }
        }

        $this->info('Analytics sync completed.');

        return self::SUCCESS;
    }
}
