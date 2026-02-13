<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\JAV\Events\OnejavJobCompleted;
use Modules\JAV\Events\OnejavJobFailed;
use Modules\JAV\Jobs\OnejavJob;
use Modules\JAV\Services\OnejavService;
use Modules\JAV\Tests\TestCase;

class OnejavJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_unique_id_is_based_on_type()
    {
        $job = new OnejavJob('new');
        $this->assertEquals('new', $job->uniqueId());

        $job2 = new OnejavJob('popular');
        $this->assertEquals('popular', $job2->uniqueId());
    }

    public function test_handle_dispatches_completed_event()
    {
        Event::fake([OnejavJobCompleted::class]);

        $mockItemsAdapter = Mockery::mock(\Modules\JAV\Services\Onejav\ItemsAdapter::class);
        $mockItemsAdapter->shouldReceive('items')
            ->andReturn(new \Modules\JAV\Dtos\Items(
                items: collect([1, 2, 3]),
                hasNextPage: false,
                nextPage: 1
            ));

        $serviceMock = Mockery::mock(OnejavService::class);
        $serviceMock->shouldReceive('new')
            ->once()
            ->andReturn($mockItemsAdapter);

        $job = new OnejavJob('new');
        $job->handle($serviceMock);

        Event::assertDispatched(OnejavJobCompleted::class, function ($event) {
            return $event->type === 'new' && $event->itemsCount === 3;
        });
    }

    public function test_failed_dispatches_failed_event()
    {
        Event::fake([OnejavJobFailed::class]);

        $exception = new \Exception('Test exception');
        $job = new OnejavJob('new');
        $job->failed($exception);

        Event::assertDispatched(OnejavJobFailed::class, function ($event) use ($exception) {
            return $event->type === 'new' && $event->exception === $exception;
        });
    }
}
