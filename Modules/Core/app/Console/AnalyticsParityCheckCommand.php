<?php

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Modules\Core\Services\AnalyticsArtifactSchemaService;
use Modules\Core\Services\AnalyticsParityService;

/**
 * Runs parity comparison and optionally exports machine-readable artifacts.
 */
class AnalyticsParityCheckCommand extends Command
{
    protected $signature = 'analytics:parity-check {--limit=100} {--print-json} {--output=}';

    protected $description = 'Compare MySQL and Mongo analytics counters for JAV movies';

    public function handle(AnalyticsParityService $parityService, AnalyticsArtifactSchemaService $schema): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $result = $parityService->check($limit);
        $artifact = $schema->parityPayload(Carbon::today()->toDateString(), $limit, $result);

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

        if ((bool) $this->option('print-json')) {
            $this->line((string) json_encode($artifact, JSON_UNESCAPED_SLASHES));
        }

        $output = trim((string) $this->option('output'));
        if ($output !== '') {
            try {
                $directory = dirname($output);
                if (! is_dir($directory)) {
                    File::ensureDirectoryExists($directory);
                }
                File::put($output, (string) json_encode($artifact, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->info(sprintf('Artifact saved: %s', $output));
            } catch (\Throwable $exception) {
                $this->error(sprintf('Failed to save artifact: %s', $exception->getMessage()));

                return self::FAILURE;
            }
        }

        $this->info(sprintf('Checked: %d, Mismatches: %d', $result['checked'], $result['mismatches']));

        return $result['mismatches'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
