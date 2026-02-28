<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature\Services;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Modules\Core\Events\Queue\QueueDispatched;
use Modules\Core\Events\Queue\QueueDispatchFailed;
use Modules\Core\Events\Queue\QueueRouted;
use Modules\Core\Facades\QueueManager;
use Modules\Core\Tests\Fixtures\Jobs\TestJob;
use Modules\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class QueueServiceTest extends TestCase
{
    #[Test]
    public function queue_dispatches_routed_and_dispatched_events_and_job_on_default_queue(): void
    {
        Event::fake([QueueRouted::class, QueueDispatched::class, QueueDispatchFailed::class]);
        Bus::fake();

        QueueManager::queue(TestJob::class, ['x' => 'a']);

        Event::assertDispatched(QueueRouted::class, fn (QueueRouted $e) => $e->jobClass === TestJob::class && $e->queueName === 'default');
        Event::assertDispatched(QueueDispatched::class, fn (QueueDispatched $e) => $e->jobClass === TestJob::class && $e->queueName === 'default');
        Event::assertNotDispatched(QueueDispatchFailed::class);
        Bus::assertDispatched(TestJob::class, fn (TestJob $job) => $job->x === 'a' && $job->queue === 'default');
    }

    #[Test]
    public function unknown_job_class_uses_default_queue(): void
    {
        Event::fake([QueueRouted::class, QueueDispatched::class]);
        Bus::fake();

        QueueManager::queue(TestJob::class, []);

        Event::assertDispatched(QueueRouted::class, fn (QueueRouted $e) => $e->queueName === 'default');
        Bus::assertDispatched(TestJob::class, fn (TestJob $job) => $job->queue === 'default');
    }

    #[Test]
    public function class_not_implementing_should_queue_throws_and_dispatches_failed(): void
    {
        Event::fake([QueueRouted::class, QueueDispatched::class, QueueDispatchFailed::class]);
        Bus::fake();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        QueueManager::queue(\stdClass::class, []);
    }

    #[Test]
    public function queue_dispatch_failed_event_fired_when_class_invalid(): void
    {
        Event::fake([QueueDispatchFailed::class]);

        try {
            QueueManager::queue('NonExistent\\JobClass', []);
        } catch (\InvalidArgumentException) {
            //
        }

        Event::assertDispatched(QueueDispatchFailed::class);
    }
}
