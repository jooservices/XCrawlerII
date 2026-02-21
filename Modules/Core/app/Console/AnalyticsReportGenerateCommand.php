<?php

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Modules\Core\Services\AnalyticsArtifactSchemaService;
use Modules\Core\Services\AnalyticsParityService;

/**
 * Generates batch analytics artifacts for parity, rollback, and archive workflows.
 */
class AnalyticsReportGenerateCommand extends Command
{
    protected $signature = 'analytics:report:generate
        {--days=7 : Number of daily parity artifacts to generate}
        {--limit=500 : Number of movies checked in each artifact}
        {--dir= : Output directory for reports}
        {--archive : Create zip archive for generated reports}
        {--rollback : Generate rollback readiness artifact}';

    protected $description = 'Generate parity reports (batch) and optional rollback/archive outputs';

    public function handle(AnalyticsParityService $parityService, AnalyticsArtifactSchemaService $schema): int
    {
        $days = max(1, (int) $this->option('days'));
        $limit = max(1, (int) $this->option('limit'));
        $baseDir = trim((string) $this->option('dir'));
        if ($baseDir === '') {
            $baseDir = storage_path((string) config('analytics.evidence.output_dir', 'logs/analytics/evidence'));
        }

        $parityDir = "{$baseDir}/parity";
        File::ensureDirectoryExists($parityDir);

        $mismatchSeen = false;
        $generated = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $artifactDate = Carbon::today()->subDays($offset);
            $dateLabel = $artifactDate->toDateString();
            $result = $parityService->check($limit);
            $mismatchSeen = $mismatchSeen || ((int) $result['mismatches'] > 0);

            $payload = $schema->parityPayload($dateLabel, $limit, $result);

            $path = "{$parityDir}/parity-{$dateLabel}.json";
            File::put($path, (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $generated[] = $path;
            $this->info("Parity artifact: {$path}");
        }

        if ((bool) $this->option('rollback')) {
            $rollbackPath = $this->writeRollbackArtifact($baseDir, $schema);
            $generated[] = $rollbackPath;
            $this->info("Rollback artifact: {$rollbackPath}");
        }

        if ((bool) $this->option('archive')) {
            $archivePath = $this->archiveArtifacts($baseDir, $generated);
            if ($archivePath === null) {
                $this->error('Archive requested but ZipArchive extension is unavailable.');

                return self::FAILURE;
            }
            $this->info("Archive created: {$archivePath}");
        }

        return $mismatchSeen ? self::FAILURE : self::SUCCESS;
    }

    private function writeRollbackArtifact(string $baseDir, AnalyticsArtifactSchemaService $schema): string
    {
        $rollbackDir = "{$baseDir}/rollback";
        File::ensureDirectoryExists($rollbackDir);
        $now = Carbon::now();

        $hasIngestRoute = Route::has('analytics.events.store');
        $legacyViewRouteExists = collect(Route::getRoutes()->getRoutes())
            ->contains(fn ($route): bool => $route->uri() === 'jav/movies/{jav}/view');

        $payload = $schema->rollbackPayload(
            $hasIngestRoute,
            $legacyViewRouteExists,
            [
                'P5 local scope: event-based analytics path active.',
                'Legacy /jav/movies/{jav}/view route should remain absent.',
            ]
        );

        $path = sprintf('%s/rollback-%s.json', $rollbackDir, $now->format('Y-m-d'));
        File::put($path, (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /**
     * @param  array<int, string>  $files
     */
    private function archiveArtifacts(string $baseDir, array $files): ?string
    {
        if (! class_exists(\ZipArchive::class)) {
            return null;
        }

        $archiveDir = "{$baseDir}/archive";
        File::ensureDirectoryExists($archiveDir);
        $archivePath = sprintf('%s/evidence-%s.zip', $archiveDir, Carbon::now()->format('Ymd_His'));

        $zip = new \ZipArchive;
        if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        foreach ($files as $file) {
            if (! File::exists($file)) {
                continue;
            }
            $relative = ltrim(str_replace($baseDir, '', $file), '/');
            $zip->addFile($file, $relative);
        }
        $zip->close();

        return $archivePath;
    }
}
