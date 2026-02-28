# 17 - Command and Queue Service

## Purpose

Centralize Artisan command execution and queued job dispatch. For Horizon: set `QUEUE_CONNECTION=redis` in `.env`; run `composer update` to install `laravel/horizon`; optionally run `php artisan horizon:install` to publish dashboard assets (config is already in place and references QueueEnum).

## Mandatory Rules

1. **Command execution:** All Artisan command runs MUST go through `CommandService` (via `schedule()` or `command()`). Do not call `Artisan::call()` directly from feature code for scheduled or ad-hoc commands that should be observable.

2. **Queue dispatch:** All queued job dispatch MUST go through `QueueService` (via `QueueManager::queue()`). **Direct usage of `Job::dispatch()`, `SomeJob::dispatch()`, or `dispatch(new Job(...))` outside `QueueService` is forbidden by policy.**

3. **QueueEnum as SSOT:** Queue names are defined only in `Modules\Core\Enums\Queue\QueueEnum`. New job classes SHOULD be registered in `QueueEnum::resolve()` for explicit routing; unregistered jobs fall back to `DEFAULT`.

4. **Horizon config:** `config/horizon.php` MUST reference `QueueEnum` values for the queue list (e.g. `QueueEnum::DEFAULT->value`). Tuning (processes, balance, tries, timeout, etc.) remains in `config/horizon.php` and is NOT moved into the enum.

5. **No direct dispatch:** Any code that dispatches a job to the queue MUST use `QueueManager::queue($jobClass, $params)`. Direct dispatch is prohibited for consistency and observability.

## Enforcement Guidance

- **Code review checklist:** Reject PRs that introduce direct `::dispatch(` or `dispatch(new ...)` for queue jobs. Confirm command execution uses `CommandService` (or the Command facade) where observability is required.
- **Future enforcement:** A git hook or static analysis rule can scan for `::dispatch(` and `dispatch(new ` outside `QueueService` (not implemented in this phase).

## Usage Examples

```php
use Modules\Core\Facades\Command;
use Modules\Core\Facades\QueueManager;

// Run the scheduler (e.g. from console kernel or a single entry command)
Command::schedule();

// Run an Artisan command with optional parameters
Command::command('cache:clear');
Command::command('some:command', ['--option' => 'value']);

// Dispatch a job via QueueService (required for all queue dispatch)
QueueManager::queue(ProcessPodcast::class, [
    'podcast' => $podcast,
]);
```

## Lifecycle Events

- **Command:** `SchedulerStarted`, `SchedulerCompleted`, `SchedulerFailed`, `CommandStarted`, `CommandCompleted`, `CommandFailed`. Payloads include `exitCode`, `durationMs`, and `command` where applicable.
- **Queue:** `QueueRouted`, `QueueDispatched`, `QueueDispatchFailed`. Payloads include `jobClass`, `queueName`; failed event includes exception summary.

Subscribers (`CommandSubscriber`, `QueueSubscriber`) listen to these events and are the hook for future DB/metrics/alerting; they must not throw (catch and log internally).

## Future Expansion (Not Implemented Now)

- DB-based routing for queue assignment.
- Load-aware or priority-based routing.
- Multiple named queues and multiple Horizon supervisors (beyond DEFAULT).

## References

- [01-module-boundaries-and-dependencies](01-module-boundaries-and-dependencies.md) — Core is domain-agnostic; Command/Queue are infra.
- [03-backend-architecture-rules](03-backend-architecture-rules.md) — Jobs/commands orchestrate services; no business logic in listeners.
