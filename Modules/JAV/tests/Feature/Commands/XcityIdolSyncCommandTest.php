<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Modules\JAV\Jobs\XcityKanaSyncJob;
use Modules\JAV\Services\XcityIdolService;
use Modules\JAV\Tests\TestCase;

class XcityIdolSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_kana_jobs_based_on_concurrency(): void
    {
        Queue::fake();

        $service = Mockery::mock(XcityIdolService::class);
        $service->shouldReceive('seedKanaUrls')
            ->once()
            ->andReturn([
                'a' => 'https://xxx.xcity.jp/idol/?ini=%E3%81%82&kana=%E3%81%82',
                'i' => 'https://xxx.xcity.jp/idol/?ini=%E3%81%84&kana=%E3%81%82',
            ]);
        $service->shouldReceive('pickSeedsForDispatch')
            ->once()
            ->withArgs(function (array $seedUrls, int $concurrency) {
                return count($seedUrls) === 2 && $concurrency === 2;
            })
            ->andReturn(collect([
                ['seed_key' => 'a', 'seed_url' => 'https://xxx.xcity.jp/idol/?ini=%E3%81%82&kana=%E3%81%82'],
                ['seed_key' => 'i', 'seed_url' => 'https://xxx.xcity.jp/idol/?ini=%E3%81%84&kana=%E3%81%82'],
            ]));
        $this->app->instance(XcityIdolService::class, $service);

        $this->artisan('jav:sync:idols', ['--concurrency' => 2])
            ->assertExitCode(0);

        Queue::assertPushed(XcityKanaSyncJob::class, 2);
        Queue::assertPushedOn('jav-idol', XcityKanaSyncJob::class);
    }
}
