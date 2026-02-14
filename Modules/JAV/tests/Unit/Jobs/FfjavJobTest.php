<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\JAV\Events\FfjavJobCompleted;
use Modules\JAV\Events\FfjavJobFailed;
use Modules\JAV\Jobs\FfjavJob;
use Modules\JAV\Services\FfjavService;
use Modules\JAV\Tests\TestCase;

class FfjavJobTest extends TestCase
{
    public function test_unique_id_is_based_on_type()
    {
        $job = new FfjavJob('new');
        $this->assertEquals('new', $job->uniqueId());

        $job2 = new FfjavJob('popular');
        $this->assertEquals('popular', $job2->uniqueId());
    }

    public function test_handle_dispatches_completed_event()
    {
        Event::fake([FfjavJobCompleted::class]);

        $mockItemsAdapter = Mockery::mock(\Modules\JAV\Services\Ffjav\ItemsAdapter::class);
        $mockItemsAdapter->shouldReceive('items')
            ->andReturn(new \Modules\JAV\Dtos\Items(
                items: collect([1, 2]),
                hasNextPage: false,
                nextPage: 1
            ));

        $serviceMock = Mockery::mock(FfjavService::class);
        $serviceMock->shouldReceive('new')->once()->andReturn($mockItemsAdapter);

        $job = new FfjavJob('new');
        $job->handle($serviceMock);

        Event::assertDispatched(FfjavJobCompleted::class, function ($event) {
            return $event->type === 'new' && $event->itemsCount === 2;
        });
    }

    public function test_failed_dispatches_failed_event()
    {
        Event::fake([FfjavJobFailed::class]);

        $exception = new \Exception('Test exception');
        $job = new FfjavJob('new');
        $job->failed($exception);

        Event::assertDispatched(FfjavJobFailed::class, function ($event) use ($exception) {
            return $event->type === 'new' && $event->exception === $exception;
        });
    }
}
