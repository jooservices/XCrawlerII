<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Event;
use Modules\JAV\Events\OnejavJobCompleted;
use Modules\JAV\Events\OnejavJobFailed;
use Modules\JAV\Jobs\OnejavJob;
use Modules\JAV\Services\Clients\OnejavClient;
use Modules\JAV\Services\OnejavService;
use Modules\JAV\Tests\TestCase;

class OnejavJobTest extends TestCase
{
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

        $client = \Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/new?page=1')
            ->andReturn($this->getMockResponse('onejav_new_15670.html'));

        $service = new OnejavService($client);

        $job = new OnejavJob('new');
        $job->handle($service);

        Event::assertDispatched(OnejavJobCompleted::class, function ($event) {
            return $event->type === 'new' && $event->itemsCount > 0;
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
