<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Contracts\Queue\ShouldQueue;
use InvalidArgumentException;
use Modules\Core\Enums\Queue\QueueEnum;
use Modules\Core\Events\Queue\QueueDispatched;
use Modules\Core\Events\Queue\QueueDispatchFailed;
use Modules\Core\Events\Queue\QueueRouted;
use Throwable;

class QueueService
{
    /**
     * Dispatch a job via the queue. All job dispatch MUST go through this service.
     * Uses QueueEnum::resolve() for queue name; unknown job class falls back to DEFAULT.
     *
     * @param  array<string, mixed>  $params  Constructor parameters for the job
     *
     * @throws InvalidArgumentException If class does not exist or does not implement ShouldQueue
     */
    public function queue(string $queueClass, array $params = []): void
    {
        if (! class_exists($queueClass)) {
            $e = new InvalidArgumentException("Job class does not exist: {$queueClass}");
            QueueDispatchFailed::dispatch($queueClass, QueueEnum::DEFAULT->value, $e);
            throw $e;
        }

        if (! is_subclass_of($queueClass, ShouldQueue::class)) {
            $e = new InvalidArgumentException('Job class must implement '.ShouldQueue::class.": {$queueClass}");
            QueueDispatchFailed::dispatch($queueClass, QueueEnum::DEFAULT->value, $e);
            throw $e;
        }

        $queueEnum = QueueEnum::resolve($queueClass);
        $queueName = $queueEnum->value;

        QueueRouted::dispatch($queueClass, $queueName);

        try {
            $job = app()->make($queueClass, $params);
            dispatch($job->onQueue($queueName));
            QueueDispatched::dispatch($queueClass, $queueName);
        } catch (Throwable $e) {
            QueueDispatchFailed::dispatch($queueClass, $queueName, $e);
            throw $e;
        }
    }
}
