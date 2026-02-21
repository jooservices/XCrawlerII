<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Modules\JAV\Dtos\Items;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Services\Ffjav\ItemsAdapter as FfjavItemsAdapter;
use Modules\JAV\Services\FfjavService;
use Modules\JAV\Services\OneFourOneJav\ItemsAdapter as OneFourOneItemsAdapter;
use Modules\JAV\Services\OneFourOneJavService;
use Modules\JAV\Services\OnejavService;
use Modules\JAV\Tests\TestCase;

class DailySyncJobEdgeTest extends TestCase
{
    // ──────────────────────────────────────────
    // Last page: no next job dispatched
    // ──────────────────────────────────────────

    public function test_handle_does_not_dispatch_when_no_next_page(): void
    {
        Queue::fake();

        $itemsAdapter = Mockery::mock(FfjavItemsAdapter::class);
        $itemsAdapter->shouldReceive('items')
            ->once()
            ->andReturn(new Items(collect(), false, 0)); // hasNextPage = false

        $service = Mockery::mock(FfjavService::class);
        $service->shouldReceive('daily')
            ->once()
            ->with('2026-02-14', 1)
            ->andReturn($itemsAdapter);

        $this->app->instance(FfjavService::class, $service);

        $job = new DailySyncJob('ffjav', '2026-02-14', 1);
        $job->handle();

        Queue::assertNothingPushed();
    }

    public function test_handle_does_not_dispatch_when_onejav_last_page(): void
    {
        Queue::fake();

        $itemsAdapter = Mockery::mock(\Modules\JAV\Services\Onejav\ItemsAdapter::class);
        $itemsAdapter->shouldReceive('items')
            ->once()
            ->andReturn(new Items(collect(), false, 0));

        $service = Mockery::mock(OnejavService::class);
        $service->shouldReceive('daily')
            ->once()
            ->with('2026-02-14', 1)
            ->andReturn($itemsAdapter);

        $this->app->instance(OnejavService::class, $service);

        $job = new DailySyncJob('onejav', '2026-02-14', 1);
        $job->handle();

        Queue::assertNothingPushed();
    }

    // ──────────────────────────────────────────
    // resolvedDate: null date uses today
    // ──────────────────────────────────────────

    public function test_unique_id_uses_today_when_date_is_null(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');

        $job = new DailySyncJob('onejav', null, 1);

        $this->assertSame('onejav:2026-03-15:1', $job->uniqueId());
    }

    // ──────────────────────────────────────────
    // Unsupported source: exception
    // ──────────────────────────────────────────

    public function test_unsupported_source_throws_invalid_argument_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported source: badSource');

        $job = new DailySyncJob('badSource', '2026-02-14', 1);
        $job->handle();
    }

    // ──────────────────────────────────────────
    // failed() logs error
    // ──────────────────────────────────────────

    public function test_failed_logs_error_with_correct_context(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('DailySyncJob failed', Mockery::on(function (array $context) {
                return $context['source'] === 'onejav'
                    && $context['date'] === '2026-02-14'
                    && $context['page'] === 1
                    && $context['error'] === 'Connection timed out';
            }));

        $job = new DailySyncJob('onejav', '2026-02-14', 1);
        $job->failed(new \RuntimeException('Connection timed out'));
    }

    // ──────────────────────────────────────────
    // backoff() returns expected array
    // ──────────────────────────────────────────

    public function test_backoff_returns_expected_values(): void
    {
        $job = new DailySyncJob('onejav', '2026-02-14', 1);

        $this->assertSame([1800, 2700, 3600], $job->backoff());
    }

    // ──────────────────────────────────────────
    // Queue fallback: empty string uses default
    // ──────────────────────────────────────────

    public function test_queue_fallback_when_queue_is_empty_string(): void
    {
        Queue::fake();

        $itemsAdapter = Mockery::mock(FfjavItemsAdapter::class);
        $itemsAdapter->shouldReceive('items')
            ->once()
            ->andReturn(new Items(collect(), true, 2));

        $service = Mockery::mock(FfjavService::class);
        $service->shouldReceive('daily')
            ->once()
            ->with('2026-02-14', 1)
            ->andReturn($itemsAdapter);

        $this->app->instance(FfjavService::class, $service);

        // Set queue to empty string
        $job = new DailySyncJob('ffjav', '2026-02-14', 1);
        $job->queue = '';
        $job->handle();

        // Should fall back to config-based queue
        $expectedQueue = (string) config('jav.content_queues.ffjav', 'jav');
        Queue::assertPushedOn($expectedQueue, DailySyncJob::class);
    }

    // ──────────────────────────────────────────
    // defaultQueueForSource: all sources
    // ──────────────────────────────────────────

    public function test_default_queue_for_onejav_uses_config(): void
    {
        Queue::fake();
        config(['jav.content_queues.onejav' => 'custom-onejav-queue']);

        $itemsAdapter = Mockery::mock(\Modules\JAV\Services\Onejav\ItemsAdapter::class);
        $itemsAdapter->shouldReceive('items')
            ->once()
            ->andReturn(new Items(collect(), true, 2));

        $service = Mockery::mock(OnejavService::class);
        $service->shouldReceive('daily')
            ->once()
            ->andReturn($itemsAdapter);

        $this->app->instance(OnejavService::class, $service);

        $job = new DailySyncJob('onejav', '2026-02-14', 1);
        $job->queue = ''; // Force defaultQueueForSource
        $job->handle();

        Queue::assertPushedOn('custom-onejav-queue', DailySyncJob::class);
    }

    public function test_default_queue_for_141jav_uses_config(): void
    {
        Queue::fake();
        config(['jav.content_queues.141jav' => 'custom-141-queue']);

        $itemsAdapter = Mockery::mock(OneFourOneItemsAdapter::class);
        $itemsAdapter->shouldReceive('items')
            ->once()
            ->andReturn(new Items(collect(), true, 2));

        $service = Mockery::mock(OneFourOneJavService::class);
        $service->shouldReceive('daily')
            ->once()
            ->andReturn($itemsAdapter);

        $this->app->instance(OneFourOneJavService::class, $service);

        $job = new DailySyncJob('141jav', '2026-02-14', 1);
        $job->queue = ''; // Force defaultQueueForSource
        $job->handle();

        Queue::assertPushedOn('custom-141-queue', DailySyncJob::class);
    }
}
