<?php

namespace Modules\Core\Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityTotals;
use Modules\Core\Tests\TestCase;
use Modules\JAV\Models\Jav;

class AnalyticsReportVerifyCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireMongo();
        AnalyticsEntityTotals::query()->delete();
    }

    public function test_verify_command_passes_for_valid_directory_artifacts(): void
    {
        $dir = $this->prepareValidEvidenceDirectory('verify-valid');

        $this->artisan('analytics:report:verify', [
            '--dir' => $dir,
            '--strict' => true,
        ])->assertExitCode(0);
    }

    public function test_verify_command_fails_for_invalid_schema_file(): void
    {
        $dir = $this->prepareValidEvidenceDirectory('verify-invalid');
        $first = File::files("{$dir}/parity")[0]->getPathname();
        $payload = json_decode((string) File::get($first), true);
        unset($payload['schema_version']);
        File::put($first, (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->artisan('analytics:report:verify', [
            '--dir' => $dir,
            '--strict' => true,
        ])->assertExitCode(1);
    }

    public function test_verify_command_passes_for_valid_archive_when_zip_available(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension unavailable.');
        }

        $dir = $this->prepareValidEvidenceDirectory('verify-archive');
        $this->artisan('analytics:report:generate', [
            '--days' => 2,
            '--limit' => 100,
            '--dir' => $dir,
            '--rollback' => true,
            '--archive' => true,
        ])->assertExitCode(0);

        $archive = File::files("{$dir}/archive")[0]->getPathname();

        $this->artisan('analytics:report:verify', [
            '--archive' => $archive,
            '--strict' => true,
        ])->assertExitCode(0);
    }

    private function prepareValidEvidenceDirectory(string $suffix): string
    {
        $jav = Jav::factory()->create([
            'views' => 8,
            'downloads' => 2,
            'source' => 'onejav',
            'code' => fake()->bothify('???-###'),
        ]);

        AnalyticsEntityTotals::query()->create([
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $jav->uuid,
            'view' => 8,
            'download' => 2,
        ]);

        $dir = storage_path("framework/testing/analytics/{$suffix}");
        File::deleteDirectory($dir);

        $this->artisan('analytics:report:generate', [
            '--days' => 2,
            '--limit' => 100,
            '--dir' => $dir,
            '--rollback' => true,
        ])->assertExitCode(0);

        return $dir;
    }

    private function requireMongo(): void
    {
        try {
            AnalyticsEntityTotals::query()->limit(1)->get();
        } catch (\Throwable $exception) {
            $this->markTestSkipped('MongoDB unavailable for evidence verify command test: '.$exception->getMessage());
        }
    }
}
