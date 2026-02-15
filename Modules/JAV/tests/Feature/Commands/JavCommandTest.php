<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Jobs\OnejavJob;
use Modules\JAV\Jobs\TagsSyncJob;
use Modules\JAV\Jobs\XcityKanaSyncJob;
use Modules\JAV\Services\AnalyticsSnapshotService;
use Modules\JAV\Services\RecommendationService;
use Modules\JAV\Services\XcityIdolService;
use Modules\JAV\Tests\TestCase;

class JavCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_without_type_runs_default_flows(): void
    {
        Queue::fake();

        $this->artisan('jav:sync', [
            '--only' => ['content'],
            '--provider' => ['onejav'],
        ])
            ->assertExitCode(0);

        Queue::assertPushedOn('jav', DailySyncJob::class, function ($job) {
            return $job->source === 'onejav' && $job->page === 1;
        });

        Queue::assertPushedOn('jav', OnejavJob::class, function ($job) {
            return $job->type === 'popular';
        });

        Queue::assertPushedOn('jav', TagsSyncJob::class, function (TagsSyncJob $job) {
            return $job->source === 'onejav';
        });
    }

    public function test_command_rejects_invalid_only_component(): void
    {
        Queue::fake();

        $this->artisan('jav:sync', [
            '--only' => ['invalid-component'],
        ])->assertExitCode(2);

        Queue::assertNothingPushed();
    }

    public function test_command_rejects_search_reset_without_confirm_flag(): void
    {
        $this->artisan('jav:sync', [
            '--only' => ['search'],
            '--search-mode' => 'reset',
        ])->assertExitCode(2);
    }

    public function test_command_can_dispatch_idol_sync_component(): void
    {
        Queue::fake();

        $service = Mockery::mock(XcityIdolService::class);
        $service->shouldReceive('seedKanaUrls')->once()->andReturn([
            'seed-a' => 'https://xxx.xcity.jp/idol/?kana=a',
            'seed-i' => 'https://xxx.xcity.jp/idol/?kana=i',
        ]);
        $service->shouldReceive('pickSeedsForDispatch')
            ->once()
            ->withArgs(function (array $seeds, int $concurrency): bool {
                return count($seeds) === 2 && $concurrency === 2;
            })
            ->andReturn(collect([
                ['seed_key' => 'seed-a', 'seed_url' => 'https://xxx.xcity.jp/idol/?kana=a'],
                ['seed_key' => 'seed-i', 'seed_url' => 'https://xxx.xcity.jp/idol/?kana=i'],
            ]));
        $this->app->instance(XcityIdolService::class, $service);

        $this->artisan('jav:sync', [
            '--only' => ['idols'],
            '--concurrency' => 2,
        ])->assertExitCode(0);

        Queue::assertPushed(XcityKanaSyncJob::class, 2);
    }

    public function test_command_can_run_analytics_component(): void
    {
        $service = Mockery::mock(AnalyticsSnapshotService::class);
        $service->shouldReceive('getSnapshot')->once()->with(7, true, false)->andReturn(['totals' => ['jav' => 1, 'actors' => 1, 'tags' => 1]]);
        $service->shouldReceive('getSnapshot')->once()->with(14, true, false)->andReturn(['totals' => ['jav' => 1, 'actors' => 1, 'tags' => 1]]);
        $service->shouldReceive('getSnapshot')->once()->with(30, true, false)->andReturn(['totals' => ['jav' => 1, 'actors' => 1, 'tags' => 1]]);
        $service->shouldReceive('getSnapshot')->once()->with(90, true, false)->andReturn(['totals' => ['jav' => 1, 'actors' => 1, 'tags' => 1]]);
        $this->app->instance(AnalyticsSnapshotService::class, $service);

        $this->artisan('jav:sync', [
            '--only' => ['analytics'],
        ])->assertExitCode(0);
    }

    public function test_command_can_run_recommendations_component_for_specific_users(): void
    {
        $service = Mockery::mock(RecommendationService::class);
        $service->shouldReceive('syncSnapshotForUserId')->once()->with(12, 15)->andReturn(true);
        $this->app->instance(RecommendationService::class, $service);

        $this->artisan('jav:sync', [
            '--only' => ['recommendations'],
            '--user-id' => [12],
            '--limit' => 15,
        ])->assertExitCode(0);
    }
}
