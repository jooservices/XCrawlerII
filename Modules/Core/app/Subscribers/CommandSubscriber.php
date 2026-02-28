<?php

declare(strict_types=1);

namespace Modules\Core\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Modules\Core\Events\Command\CommandCompleted;
use Modules\Core\Events\Command\CommandFailed;
use Modules\Core\Events\Command\CommandStarted;
use Modules\Core\Events\Command\SchedulerCompleted;
use Modules\Core\Events\Command\SchedulerFailed;
use Modules\Core\Events\Command\SchedulerStarted;

class CommandSubscriber
{
    /**
     * Register listeners for Command lifecycle events. Subscriber MUST NOT throw; catch and log internally.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(SchedulerStarted::class, [$this, 'onSchedulerStarted']);
        $events->listen(SchedulerCompleted::class, [$this, 'onSchedulerCompleted']);
        $events->listen(SchedulerFailed::class, [$this, 'onSchedulerFailed']);
        $events->listen(CommandStarted::class, [$this, 'onCommandStarted']);
        $events->listen(CommandCompleted::class, [$this, 'onCommandCompleted']);
        $events->listen(CommandFailed::class, [$this, 'onCommandFailed']);
    }

    public function onSchedulerStarted(SchedulerStarted $_event): void
    {
        // Intentionally no-op for now; lifecycle hook kept for future telemetry.
    }

    public function onSchedulerCompleted(SchedulerCompleted $_event): void
    {
        // Intentionally no-op for now; lifecycle hook kept for future telemetry.
    }

    public function onSchedulerFailed(SchedulerFailed $_event): void
    {
        // Intentionally no-op for now; lifecycle hook kept for future telemetry.
    }

    public function onCommandStarted(CommandStarted $_event): void
    {
        // Intentionally no-op for now; lifecycle hook kept for future telemetry.
    }

    public function onCommandCompleted(CommandCompleted $_event): void
    {
        // Intentionally no-op for now; lifecycle hook kept for future telemetry.
    }

    public function onCommandFailed(CommandFailed $_event): void
    {
        // Intentionally no-op for now; lifecycle hook kept for future telemetry.
    }
}
