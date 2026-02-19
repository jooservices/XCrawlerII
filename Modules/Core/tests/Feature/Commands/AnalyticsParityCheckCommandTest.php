<?php

namespace Modules\Core\Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityTotals;
use Modules\Core\Services\AnalyticsArtifactSchemaService;
use Modules\Core\Tests\TestCase;
use Modules\JAV\Models\Jav;

class AnalyticsParityCheckCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireMongo();
        AnalyticsEntityTotals::query()->delete();
    }

    public function test_command_returns_success_when_no_mismatch(): void
    {
        $jav = Jav::factory()->create([
            'views' => 10,
            'downloads' => 3,
            'source' => 'onejav',
            'code' => fake()->bothify('???-###'),
        ]);

        AnalyticsEntityTotals::query()->updateOrCreate(
            [
                'domain' => AnalyticsDomain::Jav->value,
                'entity_type' => AnalyticsEntityType::Movie->value,
                'entity_id' => $jav->uuid,
            ],
            [
                'view' => 10,
                'download' => 3,
            ]
        );

        $this->artisan('analytics:parity-check')
            ->expectsOutput('Checked: 1, Mismatches: 0')
            ->assertExitCode(0);
    }

    public function test_command_returns_failure_and_prints_mismatch_rows(): void
    {
        $jav = Jav::factory()->create([
            'views' => 10,
            'downloads' => 3,
            'source' => 'onejav',
            'code' => fake()->bothify('???-###'),
        ]);

        AnalyticsEntityTotals::query()->updateOrCreate(
            [
                'domain' => AnalyticsDomain::Jav->value,
                'entity_type' => AnalyticsEntityType::Movie->value,
                'entity_id' => $jav->uuid,
            ],
            [
                'view' => 9,
                'download' => 3,
            ]
        );

        $this->artisan('analytics:parity-check', ['--limit' => 5])
            ->expectsOutput("{$jav->code}: MySQL(10/3) vs Mongo(9/3)")
            ->expectsOutput('Checked: 1, Mismatches: 1')
            ->assertExitCode(1);
    }

    public function test_command_writes_json_artifact_when_output_option_is_used(): void
    {
        $jav = Jav::factory()->create([
            'views' => 5,
            'downloads' => 2,
            'source' => 'onejav',
            'code' => fake()->bothify('???-###'),
        ]);

        AnalyticsEntityTotals::query()->updateOrCreate(
            [
                'domain' => AnalyticsDomain::Jav->value,
                'entity_type' => AnalyticsEntityType::Movie->value,
                'entity_id' => $jav->uuid,
            ],
            [
                'view' => 5,
                'download' => 2,
            ]
        );

        $output = storage_path('framework/testing/parity-check-artifact.json');
        File::delete($output);

        $this->artisan('analytics:parity-check', [
            '--limit' => 5,
            '--output' => $output,
        ])
            ->expectsOutput("Artifact saved: {$output}")
            ->expectsOutput('Checked: 1, Mismatches: 0')
            ->assertExitCode(0);

        $this->assertTrue(File::exists($output));
        $payload = json_decode((string) File::get($output), true);

        $this->assertIsArray($payload);
        $this->assertSame(AnalyticsArtifactSchemaService::SCHEMA_VERSION, $payload['schema_version'] ?? null);
        $this->assertSame('parity', $payload['artifact_type'] ?? null);
        $this->assertSame(5, $payload['limit']);
        $this->assertSame(1, $payload['checked']);
        $this->assertSame(0, $payload['mismatches']);
        $this->assertArrayHasKey('generated_at', $payload);
        $this->assertArrayHasKey('rows', $payload);
    }

    public function test_command_prints_json_payload_when_print_json_option_is_used(): void
    {
        $jav = Jav::factory()->create([
            'views' => 7,
            'downloads' => 1,
            'source' => 'onejav',
            'code' => fake()->bothify('???-###'),
        ]);

        AnalyticsEntityTotals::query()->updateOrCreate(
            [
                'domain' => AnalyticsDomain::Jav->value,
                'entity_type' => AnalyticsEntityType::Movie->value,
                'entity_id' => $jav->uuid,
            ],
            [
                'view' => 7,
                'download' => 1,
            ]
        );

        $this->artisan('analytics:parity-check', [
            '--limit' => 5,
            '--print-json' => true,
        ])
            ->assertExitCode(0);
    }

    private function requireMongo(): void
    {
        try {
            AnalyticsEntityTotals::query()->limit(1)->get();
        } catch (\Throwable $exception) {
            $this->markTestSkipped('MongoDB unavailable for command integration test: '.$exception->getMessage());
        }
    }
}
