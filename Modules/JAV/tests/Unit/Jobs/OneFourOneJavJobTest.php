<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Event;
use Modules\JAV\Events\OneFourOneJobCompleted;
use Modules\JAV\Events\OneFourOneJobFailed;
use Modules\JAV\Jobs\OneFourOneJavJob;
use Modules\JAV\Services\Clients\OneFourOneJavClient;
use Modules\JAV\Services\OneFourOneJavService;
use Modules\JAV\Tests\TestCase;

class OneFourOneJavJobTest extends TestCase
{
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

        $client = \Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/new?page=1')
            ->andReturn($this->getMockResponse('141jav_new.html'));

        $service = new OneFourOneJavService($client);

        $job = new OneFourOneJavJob('new');
        $job->handle($service);

        Event::assertDispatched(OneFourOneJobCompleted::class, function ($event) {
            return $event->type === 'new' && $event->itemsCount > 0;
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
