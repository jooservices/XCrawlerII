<?php

namespace Modules\Core\Tests\Feature\Analytics;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Redis;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityDaily;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityMonthly;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityTotals;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityWeekly;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityYearly;
use Modules\Core\Services\AnalyticsFlushService;
use Modules\Core\Tests\TestCase;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Jav;

class AnalyticsProductionSimulationFeatureTest extends TestCase
{
    private string $prefix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requireAnalyticsInfra();
        $this->prefix = 'anl:counters:test:prod-sim:'.$this->faker->uuid();
        config(['analytics.redis_prefix' => $this->prefix]);

        $this->cleanupRedis();
        AnalyticsEntityTotals::query()->delete();
        AnalyticsEntityDaily::query()->delete();
        AnalyticsEntityWeekly::query()->delete();
        AnalyticsEntityMonthly::query()->delete();
        AnalyticsEntityYearly::query()->delete();
    }

    public function test_end_to_end_view_download_like_flow_updates_expected_analytics_counters(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create([
            'source' => 'unsupported-source',
            'views' => 0,
            'downloads' => 0,
        ]);

        $this->actingAs($user);

        $eventTimeline = [
            ['date' => '2026-01-30T08:00:00Z', 'action' => AnalyticsAction::View, 'count' => 2],
            ['date' => '2026-02-01T08:00:00Z', 'action' => AnalyticsAction::View, 'count' => 3],
            ['date' => '2026-02-03T08:00:00Z', 'action' => AnalyticsAction::View, 'count' => 1],
        ];

        $expectedViews = 0;
        foreach ($eventTimeline as $slot) {
            for ($i = 0; $i < $slot['count']; $i++) {
                $this->postJson(route('api.analytics.events.store'), [
                    'event_id' => $this->faker->uuid(),
                    'domain' => AnalyticsDomain::Jav->value,
                    'entity_type' => AnalyticsEntityType::Movie->value,
                    'entity_id' => (string) $jav->uuid,
                    'action' => $slot['action']->value,
                    'value' => 1,
                    'occurred_at' => CarbonImmutable::parse($slot['date'])->addSeconds($i)->toIso8601String(),
                ])->assertStatus(202);
            }
            $expectedViews += (int) $slot['count'];
        }

        $this->postJson(route('jav.toggle-like'), [
            'id' => $jav->id,
            'type' => AnalyticsDomain::Jav->value,
        ])->assertStatus(200)->assertJsonPath('success', true);

        $this->get(route('jav.movies.download', $jav->uuid))->assertStatus(302);

        app(AnalyticsFlushService::class)->flush();

        $totals = AnalyticsEntityTotals::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', (string) $jav->uuid)
            ->first();

        $this->assertNotNull($totals);
        $this->assertSame($expectedViews, (int) ($totals->view ?? 0));
        $this->assertSame(1, (int) ($totals->download ?? 0));
        $this->assertArrayNotHasKey('favorite', $totals->toArray());

        $this->assertSame(1, Favorite::query()
            ->where('favoritable_type', Jav::class)
            ->where('favoritable_id', $jav->id)
            ->where('user_id', $user->id)
            ->count());

        $dailyRows = AnalyticsEntityDaily::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', (string) $jav->uuid)
            ->get();
        $this->assertGreaterThanOrEqual(3, $dailyRows->count());

        $weeklyRows = AnalyticsEntityWeekly::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', (string) $jav->uuid)
            ->get();
        $this->assertGreaterThanOrEqual(1, $weeklyRows->count());

        $monthlyRows = AnalyticsEntityMonthly::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', (string) $jav->uuid)
            ->get();
        $this->assertCount(2, $monthlyRows);

        $yearlyRows = AnalyticsEntityYearly::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', (string) $jav->uuid)
            ->get();
        $this->assertCount(1, $yearlyRows);

        $jav->refresh();
        $this->assertSame($expectedViews, (int) $jav->views);
        $this->assertSame(1, (int) $jav->downloads);
    }

    private function requireAnalyticsInfra(): void
    {
        try {
            Redis::set('anl:infra:test:prod-sim', 1, 'EX', 5);
            Redis::del('anl:infra:test:prod-sim');
        } catch (\Throwable $exception) {
            $this->markTestSkipped('Redis unavailable for production simulation analytics test: '.$exception->getMessage());
        }

        try {
            AnalyticsEntityTotals::query()->limit(1)->get();
        } catch (\Throwable $exception) {
            $this->markTestSkipped('MongoDB unavailable for production simulation analytics test: '.$exception->getMessage());
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
