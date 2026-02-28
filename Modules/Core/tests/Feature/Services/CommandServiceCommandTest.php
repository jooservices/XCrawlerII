<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Modules\Core\Events\Command\CommandCompleted;
use Modules\Core\Events\Command\CommandFailed;
use Modules\Core\Events\Command\CommandStarted;
use Modules\Core\Events\Command\SchedulerCompleted;
use Modules\Core\Events\Command\SchedulerFailed;
use Modules\Core\Events\Command\SchedulerStarted;
use Modules\Core\Facades\Command;
use Modules\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class CommandServiceCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::command('core:test-ok', fn () => 0);
        Artisan::command('core:test-fail', fn () => 1);
    }

    #[Test]
    public function test_happy_schedule_dispatches_scheduler_started_then_completed_or_failed(): void
    {
        Event::fake([SchedulerStarted::class, SchedulerCompleted::class, SchedulerFailed::class]);

        Command::schedule();

        Event::assertDispatched(SchedulerStarted::class);
        $completed = Event::dispatched(SchedulerCompleted::class);
        $failed = Event::dispatched(SchedulerFailed::class);
        self::assertTrue(
            $completed->isNotEmpty() || $failed->isNotEmpty(),
            'Expected SchedulerCompleted or SchedulerFailed after SchedulerStarted'
        );
        $completedEvent = $completed->isNotEmpty() ? $completed->first()[0] ?? $completed->first() : null;
        $failedEvent = $failed->isNotEmpty() ? $failed->first()[0] ?? $failed->first() : null;
        if ($completedEvent instanceof SchedulerCompleted) {
            self::assertSame(0, $completedEvent->exitCode);
            self::assertGreaterThanOrEqual(0, $completedEvent->durationMs);
        }
        if ($failedEvent instanceof SchedulerFailed) {
            self::assertNotSame(0, $failedEvent->exitCode);
            self::assertGreaterThanOrEqual(0, $failedEvent->durationMs);
        }
    }

    #[Test]
    public function test_happy_command_dispatches_lifecycle_events_on_success(): void
    {
        Event::fake([CommandStarted::class, CommandCompleted::class, CommandFailed::class]);

        Command::command('core:test-ok');

        Event::assertDispatched(CommandStarted::class, fn (CommandStarted $e) => $e->command === 'core:test-ok');
        Event::assertDispatched(CommandCompleted::class, function (CommandCompleted $e) {
            return $e->command === 'core:test-ok'
                && $e->exitCode === 0
                && $e->durationMs >= 0;
        });
        Event::assertNotDispatched(CommandFailed::class);
    }

    #[Test]
    public function test_unhappy_command_dispatches_command_failed_on_non_zero_exit(): void
    {
        Event::fake([CommandStarted::class, CommandCompleted::class, CommandFailed::class]);

        Command::command('core:test-fail');

        Event::assertDispatched(CommandStarted::class, fn (CommandStarted $e) => $e->command === 'core:test-fail');
        Event::assertDispatched(CommandFailed::class, function (CommandFailed $e) {
            return $e->command === 'core:test-fail'
                && $e->exitCode === 1
                && $e->durationMs >= 0;
        });
        Event::assertNotDispatched(CommandCompleted::class);
    }

    #[Test]
    public function test_happy_command_passes_parameters_through(): void
    {
        Artisan::command('core:test-params {value}', function ($value) {
            return $value === 'passed' ? 0 : 1;
        });

        Event::fake([CommandCompleted::class]);

        Command::command('core:test-params', ['value' => 'passed']);

        Event::assertDispatched(CommandCompleted::class, fn (CommandCompleted $e) => $e->exitCode === 0);
    }

    #[Test]
    public function test_unhappy_invalid_command_name_throws(): void
    {
        Event::fake([CommandStarted::class, CommandCompleted::class, CommandFailed::class]);

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('nonexistent:command');

        Command::command('nonexistent:command');
    }

    #[Test]
    public function test_security_command_name_injection_like_input_is_not_executed_and_throws(): void
    {
        Event::fake([CommandStarted::class, CommandCompleted::class, CommandFailed::class]);

        $this->expectException(\Throwable::class);

        Command::command('core:test-ok; echo hacked');
    }
}
