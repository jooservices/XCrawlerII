<?php

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Modules\Core\Services\AnalyticsArtifactSchemaService;

/**
 * Verifies artifact schema compliance for directories and zip archives.
 */
class AnalyticsReportVerifyCommand extends Command
{
    protected $signature = 'analytics:report:verify
        {--dir= : Report directory containing parity/rollback artifacts}
        {--archive= : Zip archive path to verify}
        {--strict : Fail when no artifact files are found}';

    protected $description = 'Verify analytics report schema and archive integrity for CI';

    public function handle(AnalyticsArtifactSchemaService $schema): int
    {
        $archive = trim((string) $this->option('archive'));
        $strict = (bool) $this->option('strict');

        if ($archive !== '') {
            return $this->verifyArchive($archive, $schema, $strict);
        }

        $dir = trim((string) $this->option('dir'));
        if ($dir === '') {
            $dir = storage_path((string) config('analytics.evidence.output_dir', 'logs/analytics/evidence'));
        }

        return $this->verifyDirectory($dir, $schema, $strict);
    }

    private function verifyArchive(string $archivePath, AnalyticsArtifactSchemaService $schema, bool $strict): int
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->error('ZipArchive extension is unavailable.');

            return self::FAILURE;
        }

        if (! File::exists($archivePath)) {
            $this->error("Archive not found: {$archivePath}");

            return self::FAILURE;
        }

        $zip = new \ZipArchive;
        if ($zip->open($archivePath) !== true) {
            $this->error("Unable to open archive: {$archivePath}");

            return self::FAILURE;
        }

        $tempDir = storage_path('framework/testing/analytics/verify-archive-'.Carbon::now()->format('Ymd_His_u'));
        File::ensureDirectoryExists($tempDir);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (! is_string($entry) || ! str_ends_with($entry, '.json')) {
                continue;
            }

            $target = "{$tempDir}/{$entry}";
            File::ensureDirectoryExists(dirname($target));
            $content = $zip->getFromIndex($i);
            if (! is_string($content)) {
                continue;
            }
            File::put($target, $content);
        }
        $zip->close();

        $result = $this->verifyDirectory($tempDir, $schema, $strict);
        File::deleteDirectory($tempDir);

        return $result;
    }

    private function verifyDirectory(string $dir, AnalyticsArtifactSchemaService $schema, bool $strict): int
    {
        if (! is_dir($dir)) {
            $this->error("Directory not found: {$dir}");

            return self::FAILURE;
        }

        $jsonFiles = collect(File::allFiles($dir))
            ->filter(fn (\SplFileInfo $file): bool => str_ends_with($file->getFilename(), '.json'))
            ->values();

        if ($jsonFiles->isEmpty()) {
            $this->warn("No JSON artifacts found in: {$dir}");

            return $strict ? self::FAILURE : self::SUCCESS;
        }

        $invalid = 0;

        foreach ($jsonFiles as $file) {
            $path = $file->getPathname();
            $decoded = json_decode((string) File::get($path), true);
            if (! is_array($decoded)) {
                $invalid++;
                $this->error("Invalid JSON: {$path}");

                continue;
            }

            $errors = $schema->validatePayload($decoded);
            if ($errors !== []) {
                $invalid++;
                $this->error(sprintf('Schema invalid: %s (%s)', $path, implode('; ', $errors)));

                continue;
            }

            $this->line("OK: {$path}");
        }

        $checked = $jsonFiles->count();
        $this->info("Verified {$checked} artifact(s), invalid {$invalid}.");

        return $invalid === 0 ? self::SUCCESS : self::FAILURE;
    }
}
