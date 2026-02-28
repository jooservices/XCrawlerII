<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Artisan;
use Modules\Core\Events\Command\CommandCompleted;
use Modules\Core\Events\Command\CommandFailed;
use Modules\Core\Events\Command\CommandStarted;
use Modules\Core\Events\Command\SchedulerCompleted;
use Modules\Core\Events\Command\SchedulerFailed;
use Modules\Core\Events\Command\SchedulerStarted;

class CommandService
{
    /**
     * Run the scheduler (schedule:run). Dispatches lifecycle events with exitCode and durationMs.
     */
    public function schedule(): void
    {
        SchedulerStarted::dispatch();

        $start = hrtime(true);
        $exitCode = Artisan::call('schedule:run');
        $durationMs = (int) ((hrtime(true) - $start) / 1_000_000);

        if ($exitCode === 0) {
            SchedulerCompleted::dispatch($exitCode, $durationMs);
        } else {
            SchedulerFailed::dispatch($exitCode, $durationMs);
        }
    }

    /**
     * Run an Artisan command. Dispatches lifecycle events with command, exitCode, durationMs.
     */
    public function command(string $command, array $params = []): void
    {
        CommandStarted::dispatch($command);

        $start = hrtime(true);
        $exitCode = Artisan::call($command, $params);
        $durationMs = (int) ((hrtime(true) - $start) / 1_000_000);

        if ($exitCode === 0) {
            CommandCompleted::dispatch($command, $exitCode, $durationMs);
        } else {
            CommandFailed::dispatch($command, $exitCode, $durationMs);
        }
    }
}
