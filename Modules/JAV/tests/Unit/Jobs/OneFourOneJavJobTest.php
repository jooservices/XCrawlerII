<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\JAV\Events\OneFourOneJobCompleted;
use Modules\JAV\Events\OneFourOneJobFailed;
use Modules\JAV\Jobs\OneFourOneJavJob;
use Modules\JAV\Services\OneFourOneJavService;
use Modules\JAV\Tests\TestCase;

class OneFourOneJavJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_unique_id_is_based_on_type()
    {
        $job = new OneFourOneJavJob('new');
        $this->assertEquals('new', $job->uniqueId());

        $job2 = new OneFourOneJavJob('popular');
        $this->assertEquals('popular', $job2->uniqueId());
    }

    public function test_handle_dispatches_completed_event()
    {
        Event::fake([OneFourOneJobCompleted::class]);

        $mockItemsAdapter = Mockery::mock(\Modules\JAV\Services\OneFourOneJav\ItemsAdapter::class);
        $mockItemsAdapter->shouldReceive('items')
            ->andReturn(new \Modules\JAV\Dtos\Items(
                items: collect([1, 2, 3]),
                hasNextPage: false,
                nextPage: 1
            ));

        $serviceMock = Mockery::mock(OneFourOneJavService::class);
        $serviceMock->shouldReceive('new')
            ->once()
            ->andReturn($mockItemsAdapter);

        $job = new OneFourOneJavJob('new');
        $job->handle($serviceMock);

        Event::assertDispatched(OneFourOneJobCompleted::class, function ($event) {
            return $event->type === 'new' && $event->itemsCount === 3;
        });
    }

    public function test_failed_dispatches_failed_event()
    {
        Event::fake([OneFourOneJobFailed::class]);

        $exception = new \Exception('Test exception');
        $job = new OneFourOneJavJob('new');
        $job->failed($exception);

        Event::assertDispatched(OneFourOneJobFailed::class, function ($event) use ($exception) {
            return $event->type === 'new' && $event->exception === $exception;
        });
    }
}
