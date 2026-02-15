<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Support\Facades\Queue;
use Mockery;
use Modules\JAV\Jobs\XcityKanaSyncJob;
use Modules\JAV\Services\XcityIdolService;
use Modules\JAV\Tests\TestCase;

class JavSyncIdolsCommandAdvancedTest extends TestCase
{
    public function test_command_rejects_invalid_source(): void
    {
        $this->artisan('jav:sync:idols', [
            '--source' => 'invalid',
        ])->assertExitCode(2);
    }

    public function test_command_returns_success_when_no_seeds_are_available(): void
    {
        Queue::fake();

        $service = Mockery::mock(XcityIdolService::class);
        $service->shouldReceive('seedKanaUrls')->once()->andReturn([]);
        $service->shouldNotReceive('pickSeedsForDispatch');
        $this->app->instance(XcityIdolService::class, $service);

        $this->artisan('jav:sync:idols')->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_command_returns_success_when_all_seeds_are_currently_running(): void
    {
        Queue::fake();

        $service = Mockery::mock(XcityIdolService::class);
        $service->shouldReceive('seedKanaUrls')->once()->andReturn([
            'seed-a' => 'https://xxx.xcity.jp/idol/?kana=a',
        ]);
        $service->shouldReceive('pickSeedsForDispatch')->once()->andReturn(collect());
        $this->app->instance(XcityIdolService::class, $service);

        $this->artisan('jav:sync:idols')->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_command_honors_queue_option_when_dispatching_jobs(): void
    {
        Queue::fake();

        $service = Mockery::mock(XcityIdolService::class);
        $service->shouldReceive('seedKanaUrls')->once()->andReturn([
            'seed-a' => 'https://xxx.xcity.jp/idol/?kana=a',
        ]);
        $service->shouldReceive('pickSeedsForDispatch')->once()->andReturn(collect([
            ['seed_key' => 'seed-a', 'seed_url' => 'https://xxx.xcity.jp/idol/?kana=a'],
        ]));
        $this->app->instance(XcityIdolService::class, $service);

        $this->artisan('jav:sync:idols', [
            '--queue' => 'xcity',
        ])->assertExitCode(0);

        Queue::assertPushedOn('xcity', XcityKanaSyncJob::class);
    }
}
