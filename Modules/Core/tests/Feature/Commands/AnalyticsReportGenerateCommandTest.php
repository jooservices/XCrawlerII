<?php

namespace Modules\Core\Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityTotals;
use Modules\Core\Services\AnalyticsArtifactSchemaService;
use Modules\Core\Tests\TestCase;
use Modules\JAV\Models\Jav;

class AnalyticsReportGenerateCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireMongo();
        AnalyticsEntityTotals::query()->delete();
    }

    public function test_command_generates_batch_parity_and_rollback_artifacts(): void
    {
        $jav = Jav::factory()->create([
            'views' => 15,
            'downloads' => 2,
            'source' => 'onejav',
            'code' => fake()->bothify('???-###'),
        ]);

        AnalyticsEntityTotals::query()->create([
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $jav->uuid,
            'view' => 15,
            'download' => 2,
        ]);

        $dir = storage_path('framework/testing/analytics/evidence-cmd');
        File::deleteDirectory($dir);

        $this->artisan('analytics:report:generate', [
            '--days' => 3,
            '--limit' => 100,
            '--dir' => $dir,
            '--rollback' => true,
        ])->assertExitCode(0);

        $parityFiles = File::files("{$dir}/parity");
        $this->assertCount(3, $parityFiles);

        $sample = json_decode((string) File::get($parityFiles[0]->getPathname()), true);
        $this->assertIsArray($sample);
        $this->assertSame(AnalyticsArtifactSchemaService::SCHEMA_VERSION, $sample['schema_version'] ?? null);
        $this->assertSame('parity', $sample['artifact_type'] ?? null);
        $this->assertSame(0, (int) ($sample['mismatches'] ?? -1));
        $this->assertArrayHasKey('artifact_date', $sample);
        $this->assertArrayHasKey('generated_at', $sample);

        $rollbackFiles = File::files("{$dir}/rollback");
        $this->assertCount(1, $rollbackFiles);
        $rollback = json_decode((string) File::get($rollbackFiles[0]->getPathname()), true);
        $this->assertIsArray($rollback);
        $this->assertSame(AnalyticsArtifactSchemaService::SCHEMA_VERSION, $rollback['schema_version'] ?? null);
        $this->assertSame('rollback', $rollback['artifact_type'] ?? null);
        $this->assertArrayHasKey('ingest_route_present', $rollback);
        $this->assertArrayHasKey('legacy_movie_view_route_present', $rollback);
    }

    public function test_command_can_archive_generated_artifacts_when_zip_is_available(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension unavailable.');
        }

        $jav = Jav::factory()->create([
            'views' => 4,
            'downloads' => 1,
            'source' => 'onejav',
            'code' => fake()->bothify('???-###'),
        ]);

        AnalyticsEntityTotals::query()->create([
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $jav->uuid,
            'view' => 4,
            'download' => 1,
        ]);

        $dir = storage_path('framework/testing/analytics/evidence-cmd-archive');
        File::deleteDirectory($dir);

        $this->artisan('analytics:report:generate', [
            '--days' => 2,
            '--limit' => 100,
            '--dir' => $dir,
            '--rollback' => true,
            '--archive' => true,
        ])->assertExitCode(0);

        $archives = File::files("{$dir}/archive");
        $this->assertCount(1, $archives);
        $this->assertStringEndsWith('.zip', $archives[0]->getFilename());
    }

    private function requireMongo(): void
    {
        try {
            AnalyticsEntityTotals::query()->limit(1)->get();
        } catch (\Throwable $exception) {
            $this->markTestSkipped('MongoDB unavailable for evidence command test: '.$exception->getMessage());
        }
    }
}
