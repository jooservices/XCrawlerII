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

    public function onSchedulerStarted(SchedulerStarted $event): void
    {
        try {
            (void) $event;
            // Placeholder: DB/metrics/observability hook here later.
        } catch (\Throwable $e) {
            Log::warning('CommandSubscriber::onSchedulerStarted failed', ['exception' => $e->getMessage()]);
        }
    }

    public function onSchedulerCompleted(SchedulerCompleted $event): void
    {
        try {
            (void) $event;
            // Placeholder: DB/metrics (exitCode, durationMs) here later.
        } catch (\Throwable $e) {
            Log::warning('CommandSubscriber::onSchedulerCompleted failed', ['exception' => $e->getMessage()]);
        }
    }

    public function onSchedulerFailed(SchedulerFailed $event): void
    {
        try {
            (void) $event;
            // Placeholder: DB/metrics/alerting (exitCode, durationMs) here later.
        } catch (\Throwable $e) {
            Log::warning('CommandSubscriber::onSchedulerFailed failed', ['exception' => $e->getMessage()]);
        }
    }

    public function onCommandStarted(CommandStarted $event): void
    {
        try {
            (void) $event;
            // Placeholder: DB/metrics (command name) here later.
        } catch (\Throwable $e) {
            Log::warning('CommandSubscriber::onCommandStarted failed', ['exception' => $e->getMessage()]);
        }
    }

    public function onCommandCompleted(CommandCompleted $event): void
    {
        try {
            (void) $event;
            // Placeholder: DB/metrics (command, exitCode, durationMs) here later.
        } catch (\Throwable $e) {
            Log::warning('CommandSubscriber::onCommandCompleted failed', ['exception' => $e->getMessage()]);
        }
    }

    public function onCommandFailed(CommandFailed $event): void
    {
        try {
            (void) $event;
            // Placeholder: DB/metrics/alerting (command, exitCode, durationMs) here later.
        } catch (\Throwable $e) {
            Log::warning('CommandSubscriber::onCommandFailed failed', ['exception' => $e->getMessage()]);
        }
    }
}
