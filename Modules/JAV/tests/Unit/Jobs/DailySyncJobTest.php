<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Queue;
use Mockery;
use Modules\JAV\Dtos\Items;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Services\Onejav\ItemsAdapter;
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

        $mockItemsAdapter = Mockery::mock(ItemsAdapter::class);
        $mockItemsAdapter->shouldReceive('items')
            ->once()
            ->andReturn(new Items(
                items: collect([1]),
                hasNextPage: true,
                nextPage: 2
            ));

        $service = Mockery::mock(OnejavService::class);
        $service->shouldReceive('daily')
            ->with('2026-02-14', 1)
            ->once()
            ->andReturn($mockItemsAdapter);
        $this->app->instance(OnejavService::class, $service);

        $job = new DailySyncJob('onejav', '2026-02-14', 1);
        $job->handle();

        Queue::assertPushedOn('jav', DailySyncJob::class, function (DailySyncJob $nextJob) {
            return $nextJob->source === 'onejav'
                && $nextJob->date === '2026-02-14'
                && $nextJob->page === 2;
        });
    }
}
