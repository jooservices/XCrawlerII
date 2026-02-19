<?php

namespace Modules\Core\Tests\Feature\Jobs;

use Illuminate\Support\Facades\Redis;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Jobs\FlushAnalyticsCountersJob;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityDaily;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityMonthly;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityTotals;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityWeekly;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityYearly;
use Modules\Core\Tests\TestCase;
use Modules\JAV\Models\Jav;

class FlushAnalyticsCountersJobTest extends TestCase
{
    private string $prefix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requireAnalyticsInfra();
        $this->prefix = 'anl:counters:test:job:'.$this->faker->uuid();
        config(['analytics.redis_prefix' => $this->prefix]);

        $keys = Redis::keys("{$this->prefix}:*");
        if ($keys !== [] && $keys !== null) {
            Redis::del($keys);
        }

        AnalyticsEntityTotals::query()->delete();
        AnalyticsEntityDaily::query()->delete();
        AnalyticsEntityWeekly::query()->delete();
        AnalyticsEntityMonthly::query()->delete();
        AnalyticsEntityYearly::query()->delete();
    }

    public function test_job_flushes_redis_counters_to_all_rollup_buckets(): void
    {
        $jav = Jav::factory()->create([
            'views' => 0,
            'downloads' => 0,
            'source' => 'onejav',
        ]);

        $date = now()->toDateString();
        $key = "{$this->prefix}:".AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$jav->uuid}";
        Redis::hset($key, AnalyticsAction::View->value, 3);
        Redis::hset($key, AnalyticsAction::View->value.":{$date}", 3);

        $job = new FlushAnalyticsCountersJob;
        $job->handle(app(\Modules\Core\Services\AnalyticsFlushService::class));

        $totals = AnalyticsEntityTotals::query()
            ->where('entity_id', $jav->uuid)
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame(3, (int) $totals->view);

        $this->assertNotNull(AnalyticsEntityDaily::query()
            ->where('entity_id', $jav->uuid)
            ->where('date', $date)
            ->first());
        $this->assertNotNull(AnalyticsEntityWeekly::query()->where('entity_id', $jav->uuid)->first());
        $this->assertNotNull(AnalyticsEntityMonthly::query()->where('entity_id', $jav->uuid)->first());
        $this->assertNotNull(AnalyticsEntityYearly::query()->where('entity_id', $jav->uuid)->first());
    }

    public function test_job_is_idempotent_when_no_keys_left_after_first_flush(): void
    {
        $jav = Jav::factory()->create([
            'views' => 0,
            'downloads' => 0,
            'source' => 'onejav',
        ]);

        $date = now()->toDateString();
        $key = "{$this->prefix}:".AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$jav->uuid}";
        Redis::hset($key, AnalyticsAction::View->value, 1);
        Redis::hset($key, AnalyticsAction::View->value.":{$date}", 1);

        $job = new FlushAnalyticsCountersJob;
        $job->handle(app(\Modules\Core\Services\AnalyticsFlushService::class));
        $job->handle(app(\Modules\Core\Services\AnalyticsFlushService::class));

        $totals = AnalyticsEntityTotals::query()
            ->where('entity_id', $jav->uuid)
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame(1, (int) $totals->view);
    }

    private function requireAnalyticsInfra(): void
    {
        try {
            Redis::set('anl:infra:test:job', 1, 'EX', 5);
            Redis::del('anl:infra:test:job');
        } catch (\Throwable $exception) {
            $this->markTestSkipped('Redis unavailable for flush job test: '.$exception->getMessage());
        }

        try {
            AnalyticsEntityTotals::query()->limit(1)->get();
        } catch (\Throwable $exception) {
            $this->markTestSkipped('MongoDB unavailable for flush job test: '.$exception->getMessage());
        }
    }
}
