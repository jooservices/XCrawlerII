<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Queue;
use Modules\JAV\Dtos\Items;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Services\Clients\OnejavClient;
use Modules\JAV\Services\Ffjav\ItemsAdapter as FfjavItemsAdapter;
use Modules\JAV\Services\FfjavService;
use Modules\JAV\Services\OneFourOneJav\ItemsAdapter as OneFourOneItemsAdapter;
use Modules\JAV\Services\OneFourOneJavService;
use Modules\JAV\Services\OnejavService;
use Modules\JAV\Tests\TestCase;

class DailySyncJobTest extends TestCase
{
    public function test_handle_dispatches_next_page_to_jav_idol_queue_when_available(): void
    {
        Queue::fake();

        $itemsAdapter = \Mockery::mock(FfjavItemsAdapter::class);
        $itemsAdapter->shouldReceive('items')
            ->once()
            ->andReturn(new Items(collect(), true, 2));

        $service = \Mockery::mock(FfjavService::class);
        $service->shouldReceive('daily')
            ->once()
            ->with('2026-02-14', 1)
            ->andReturn($itemsAdapter);

        $this->app->instance(FfjavService::class, $service);

        $job = new DailySyncJob('ffjav', '2026-02-14', 1);
        $job->onQueue('jav-idol');
        $job->handle();

        Queue::assertPushedOn('jav-idol', DailySyncJob::class, function (DailySyncJob $nextJob): bool {
            return $nextJob->source === 'ffjav'
                && $nextJob->date === '2026-02-14'
                && $nextJob->page === 2;
        });
    }

    public function test_unique_id_contains_source_date_and_page(): void
    {
        $job = new DailySyncJob('onejav', '2026-02-14', 3);

        $this->assertEquals('onejav:2026-02-14:3', $job->uniqueId());
    }

    public function test_handle_dispatches_next_page_when_available(): void
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

        Queue::assertPushedOn('onejav', DailySyncJob::class, function (DailySyncJob $nextJob): bool {
            return $nextJob->source === 'onejav'
                && $nextJob->date === '2026-02-14'
                && $nextJob->page === 2;
        });
    }

    public function test_handle_dispatches_next_page_to_141_default_queue_when_available(): void
    {
        Queue::fake();
        config(['jav.content_queues.141jav' => 'queue-141-test']);

        $itemsAdapter = \Mockery::mock(OneFourOneItemsAdapter::class);
        $itemsAdapter->shouldReceive('items')
            ->once()
            ->andReturn(new Items(collect(), true, 2));

        $service = \Mockery::mock(OneFourOneJavService::class);
        $service->shouldReceive('daily')
            ->once()
            ->with('2026-02-14', 1)
            ->andReturn($itemsAdapter);

        $this->app->instance(OneFourOneJavService::class, $service);

        $job = new DailySyncJob('141jav', '2026-02-14', 1);
        $job->handle();

        Queue::assertPushedOn('queue-141-test', DailySyncJob::class, function (DailySyncJob $nextJob): bool {
            return $nextJob->source === '141jav'
                && $nextJob->date === '2026-02-14'
                && $nextJob->page === 2;
        });
    }

    public function test_handle_dispatches_next_page_to_ffjav_default_queue_when_available(): void
    {
        Queue::fake();
        config(['jav.content_queues.ffjav' => 'queue-ffjav-test']);

        $itemsAdapter = \Mockery::mock(FfjavItemsAdapter::class);
        $itemsAdapter->shouldReceive('items')
            ->once()
            ->andReturn(new Items(collect(), true, 2));

        $service = \Mockery::mock(FfjavService::class);
        $service->shouldReceive('daily')
            ->once()
            ->with('2026-02-14', 1)
            ->andReturn($itemsAdapter);

        $this->app->instance(FfjavService::class, $service);

        $job = new DailySyncJob('ffjav', '2026-02-14', 1);
        $job->handle();

        Queue::assertPushedOn('queue-ffjav-test', DailySyncJob::class, function (DailySyncJob $nextJob): bool {
            return $nextJob->source === 'ffjav'
                && $nextJob->date === '2026-02-14'
                && $nextJob->page === 2;
        });
    }
}
