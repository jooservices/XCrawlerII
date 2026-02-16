<?php

namespace Modules\Core\Tests\Unit\Observability;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Queue;
use Modules\Core\Jobs\SendObsTelemetryJob;
use Modules\Core\Logging\ObsMonologHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class ObsMonologHandlerTest extends TestCase
{
    public function test_it_dispatches_obs_job_when_enabled(): void
    {
        Queue::fake();

        config([
            'services.obs.enabled' => true,
            'services.obs.queue_enabled' => true,
            'services.obs.queue_name' => 'obs-telemetry',
            'services.obs.service_name' => 'xcrawlerii',
            'app.env' => 'testing',
        ]);

        $handler = new ObsMonologHandler;
        $handler->handle(new LogRecord(
            datetime: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            channel: 'stack',
            level: Level::Info,
            message: 'Test application log',
            context: ['traceId' => 'trace-obs-handler'],
        ));

        Queue::assertPushedOn('obs-telemetry', SendObsTelemetryJob::class, function (SendObsTelemetryJob $job): bool {
            return ($job->payload['eventType'] ?? null) === 'app.log'
                && ($job->payload['env'] ?? null) === 'testing';
        });
    }

    public function test_it_noops_when_obs_is_disabled(): void
    {
        Queue::fake();

        config([
            'services.obs.enabled' => false,
            'services.obs.queue_enabled' => true,
            'services.obs.queue_name' => 'obs-telemetry',
        ]);

        $handler = new ObsMonologHandler;
        $handler->handle(new LogRecord(
            datetime: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            channel: 'stack',
            level: Level::Info,
            message: 'Test log without obs',
            context: [],
        ));

        Queue::assertNothingPushed();
    }

    public function test_it_skips_records_marked_with_obs_skip_flag(): void
    {
        Queue::fake();

        config([
            'services.obs.enabled' => true,
            'services.obs.queue_enabled' => true,
            'services.obs.queue_name' => 'obs-telemetry',
        ]);

        $handler = new ObsMonologHandler;
        $handler->handle(new LogRecord(
            datetime: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            channel: 'stack',
            level: Level::Info,
            message: 'skip this',
            context: ['__obs_skip' => true],
        ));

        Queue::assertNothingPushed();
    }

    public function test_it_normalizes_weird_context_values_without_throwing(): void
    {
        Queue::fake();

        config([
            'services.obs.enabled' => true,
            'services.obs.queue_enabled' => true,
            'services.obs.queue_name' => 'obs-telemetry',
            'services.obs.service_name' => 'xcrawlerii',
            'app.env' => 'testing',
        ]);

        $handler = new ObsMonologHandler;
        $handler->handle(new LogRecord(
            datetime: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            channel: 'stack',
            level: Level::Error,
            message: 'complex context',
            context: [
                'exception' => new \RuntimeException('boom'),
                'object' => new class
                {
                    public string $name = 'demo';
                },
                'when' => new DateTimeImmutable('now', new DateTimeZone('UTC')),
                'nested' => ['token' => 'abc'],
            ],
        ));

        Queue::assertPushed(SendObsTelemetryJob::class, function (SendObsTelemetryJob $job): bool {
            return ($job->payload['eventType'] ?? null) === 'app.log'
                && isset($job->payload['context']['exception']['type'])
                && ($job->payload['context']['exception']['message'] ?? null) === 'boom'
                && isset($job->payload['context']['object']['type'])
                && is_string($job->payload['context']['when']);
        });
    }
}
