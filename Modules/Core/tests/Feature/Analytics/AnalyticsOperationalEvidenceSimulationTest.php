<?php

namespace Modules\Core\Tests\Feature\Analytics;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityDaily;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityMonthly;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityTotals;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityWeekly;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityYearly;
use Modules\Core\Services\AnalyticsArtifactSchemaService;
use Modules\Core\Services\AnalyticsFlushService;
use Modules\Core\Services\AnalyticsIngestService;
use Modules\Core\Tests\TestCase;
use Modules\JAV\Models\Jav;

class AnalyticsOperationalEvidenceSimulationTest extends TestCase
{
    private string $prefix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requireAnalyticsInfra();
        $this->prefix = 'anl:counters:test:ops:'.$this->faker->uuid();
        config([
            'analytics.redis_prefix' => $this->prefix,
        ]);

        $this->cleanupRedis();
        AnalyticsEntityTotals::query()->delete();
        AnalyticsEntityDaily::query()->delete();
        AnalyticsEntityWeekly::query()->delete();
        AnalyticsEntityMonthly::query()->delete();
        AnalyticsEntityYearly::query()->delete();
    }

    public function test_simulated_seven_day_parity_artifacts_and_time_buckets_are_consistent(): void
    {
        $jav = Jav::factory()->create([
            'views' => 0,
            'downloads' => 0,
            'source' => 'onejav',
        ]);

        $artifactDir = storage_path('framework/testing/analytics/parity-sim');
        File::deleteDirectory($artifactDir);
        File::ensureDirectoryExists($artifactDir);

        $ingest = app(AnalyticsIngestService::class);
        $start = CarbonImmutable::parse('2026-02-10 12:00:00');
        $expectedViews = 0;
        $expectedDownloads = 0;

        for ($i = 0; $i < 7; $i++) {
            $day = $start->addDays($i);
            $date = $day->toDateString();

            $viewCount = $i + 1;
            $downloadCount = $i % 3;

            for ($v = 0; $v < $viewCount; $v++) {
                $ingest->ingest([
                    'event_id' => $this->faker->uuid(),
                    'domain' => AnalyticsDomain::Jav->value,
                    'entity_type' => AnalyticsEntityType::Movie->value,
                    'entity_id' => $jav->uuid,
                    'action' => AnalyticsAction::View->value,
                    'value' => 1,
                    'occurred_at' => $day->addSeconds($v)->toIso8601String(),
                ]);
            }

            for ($d = 0; $d < $downloadCount; $d++) {
                $ingest->ingest([
                    'event_id' => $this->faker->uuid(),
                    'domain' => AnalyticsDomain::Jav->value,
                    'entity_type' => AnalyticsEntityType::Movie->value,
                    'entity_id' => $jav->uuid,
                    'action' => AnalyticsAction::Download->value,
                    'value' => 1,
                    'occurred_at' => $day->addMinutes($d + 1)->toIso8601String(),
                ]);
            }

            $expectedViews += $viewCount;
            $expectedDownloads += $downloadCount;

            app(AnalyticsFlushService::class)->flush();

            $artifactPath = "{$artifactDir}/{$date}.json";
            $this->artisan('analytics:parity-check', [
                '--limit' => 100,
                '--output' => $artifactPath,
            ])->assertExitCode(0);

            $this->assertTrue(File::exists($artifactPath));
            $payload = json_decode((string) File::get($artifactPath), true);
            $this->assertSame(AnalyticsArtifactSchemaService::SCHEMA_VERSION, $payload['schema_version'] ?? null);
            $this->assertSame('parity', $payload['artifact_type'] ?? null);
            $this->assertSame(0, (int) ($payload['mismatches'] ?? -1));
            $this->assertGreaterThanOrEqual(1, (int) ($payload['checked'] ?? 0));
            $this->assertArrayHasKey('generated_at', $payload);
        }

        $this->assertCount(7, File::files($artifactDir));

        $totals = AnalyticsEntityTotals::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame($expectedViews, (int) $totals->view);
        $this->assertSame($expectedDownloads, (int) $totals->download);

        $this->assertSame(7, AnalyticsEntityDaily::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->count());

        $this->assertGreaterThanOrEqual(2, AnalyticsEntityWeekly::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->count());

        $this->assertSame(1, AnalyticsEntityMonthly::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->count());

        $this->assertSame(1, AnalyticsEntityYearly::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->count());
    }

    public function test_ingest_writes_without_runtime_feature_toggle(): void
    {
        $eventId = $this->faker->uuid();
        $entityId = fake()->uuid();

        $payload = [
            'event_id' => $eventId,
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $entityId,
            'action' => AnalyticsAction::View->value,
            'value' => 1,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(202)
            ->assertJson(['status' => 'accepted']);

        $counterKey = "{$this->prefix}:".AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$entityId}";
        $this->assertSame(1, (int) Redis::hget($counterKey, AnalyticsAction::View->value));
        $this->assertSame(1, (int) Redis::hget($counterKey, AnalyticsAction::View->value.':2026-02-19'));
        $this->assertSame('1', (string) Redis::get("anl:evt:{$eventId}"));
    }

    private function requireAnalyticsInfra(): void
    {
        try {
            Redis::set('anl:infra:test:ops', 1, 'EX', 5);
            Redis::del('anl:infra:test:ops');
        } catch (\Throwable $exception) {
            $this->markTestSkipped('Redis unavailable for operational simulation test: '.$exception->getMessage());
        }

        try {
            AnalyticsEntityTotals::query()->limit(1)->get();
        } catch (\Throwable $exception) {
            $this->markTestSkipped('MongoDB unavailable for operational simulation test: '.$exception->getMessage());
        }
    }

    private function cleanupRedis(): void
    {
        $counterKeys = Redis::keys("{$this->prefix}:*");
        if ($counterKeys !== [] && $counterKeys !== null) {
            Redis::del($counterKeys);
        }

        $eventKeys = Redis::keys('anl:evt:*');
        if ($eventKeys !== [] && $eventKeys !== null) {
            Redis::del($eventKeys);
        }
    }
}
