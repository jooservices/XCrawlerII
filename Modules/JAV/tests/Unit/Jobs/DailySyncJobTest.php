<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Services\Clients\OnejavClient;
use Modules\JAV\Services\OnejavService;
use Modules\JAV\Tests\TestCase;

class DailySyncJobTest extends TestCase
{
    public function test_unique_id_contains_source_date_and_page()
    {
        $job = new DailySyncJob('onejav', '2026-02-14', 3);
        $this->assertEquals('onejav:2026-02-14:3', $job->uniqueId());
    }

    public function test_handle_dispatches_next_page_when_available()
    {
        Queue::fake();

        $client = \Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/2026/02/14')
            ->andReturn($this->getMockResponse('onejav_daily_min_page_1_with_next.html'));

        $this->app->instance(OnejavService::class, new OnejavService($client));

        $job = new DailySyncJob('onejav', '2026-02-14', 1);
        $job->handle();

        Queue::assertPushedOn('jav', DailySyncJob::class, function (DailySyncJob $nextJob) {
            return $nextJob->source === 'onejav'
                && $nextJob->date === '2026-02-14'
                && $nextJob->page === 2;
        });
    }
}
