<?php

declare(strict_types=1);

namespace Modules\Core\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Modules\Core\Events\Queue\QueueDispatched;
use Modules\Core\Events\Queue\QueueDispatchFailed;
use Modules\Core\Events\Queue\QueueRouted;

class QueueSubscriber
{
    /**
     * Register listeners for Queue lifecycle events. Subscriber MUST NOT throw; catch and log internally.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(QueueRouted::class, [$this, 'onQueueRouted']);
        $events->listen(QueueDispatched::class, [$this, 'onQueueDispatched']);
        $events->listen(QueueDispatchFailed::class, [$this, 'onQueueDispatchFailed']);
    }

    public function onQueueRouted(QueueRouted $_event): void
    {
        // Intentionally no-op for now; lifecycle hook kept for future telemetry.
    }

    public function onQueueDispatched(QueueDispatched $_event): void
    {
        // Intentionally no-op for now; lifecycle hook kept for future telemetry.
    }

    public function onQueueDispatchFailed(QueueDispatchFailed $event): void
    {
        try {
            // Placeholder: DB/metrics/alerting (jobClass, queueName, exception summary) here later.
            $summary = $event->getExceptionSummary();
            if ($summary !== null) {
                Log::error('Queue dispatch failed', [
                    'jobClass' => $event->jobClass,
                    'queueName' => $event->queueName,
                    'exception' => $summary,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('QueueSubscriber::onQueueDispatchFailed failed', ['exception' => $e->getMessage()]);
        }
    }
}
