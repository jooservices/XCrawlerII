<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Event;
use Modules\JAV\Events\FfjavJobCompleted;
use Modules\JAV\Events\FfjavJobFailed;
use Modules\JAV\Jobs\FfjavJob;
use Modules\JAV\Services\Clients\FfjavClient;
use Modules\JAV\Services\CrawlerPaginationStateService;
use Modules\JAV\Services\CrawlerResponseCacheService;
use Modules\JAV\Services\CrawlerStatusPolicyService;
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

        $client = \Mockery::mock(FfjavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/javtorrent')
            ->andReturn($this->getMockResponse('ffjav_new.html'));

        $service = new FfjavService(
            $client,
            app(CrawlerResponseCacheService::class),
            app(CrawlerPaginationStateService::class),
            app(CrawlerStatusPolicyService::class)
        );

        $job = new FfjavJob('new');
        $job->handle($service);

        Event::assertDispatched(FfjavJobCompleted::class, function ($event) {
            return $event->type === 'new' && $event->itemsCount > 0;
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
