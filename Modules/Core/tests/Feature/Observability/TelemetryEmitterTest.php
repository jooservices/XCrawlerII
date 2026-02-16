<?php

namespace Modules\Core\Tests\Feature\Observability;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Core\Jobs\SendObsTelemetryJob;
use Modules\Core\Observability\Contracts\ObservabilityClientInterface;
use Modules\Core\Observability\Contracts\TelemetryEmitterInterface;
use Modules\Core\Observability\TelemetryEmitter;
use Tests\TestCase;

class TelemetryEmitterTest extends TestCase
{
    public function test_emitter_dispatches_obs_job_when_queue_enabled(): void
    {
        Queue::fake();

        config([
            'services.obs.enabled' => true,
            'services.obs.queue_enabled' => true,
            'services.obs.queue_name' => 'obs-telemetry',
            'services.obs.service_name' => 'xcrawlerii',
            'app.env' => 'testing',
        ]);

        app(TelemetryEmitterInterface::class)->emit('queue.job.started', ['job_name' => 'DemoJob']);

        Queue::assertPushedOn('obs-telemetry', SendObsTelemetryJob::class, function (SendObsTelemetryJob $job): bool {
            return ($job->payload['eventType'] ?? null) === 'queue.job.started'
                && ($job->payload['env'] ?? null) === 'testing';
        });
    }

    public function test_send_obs_telemetry_job_posts_without_real_request(): void
    {
        config([
            'services.obs.enabled' => true,
            'services.obs.base_url' => 'https://obs.example.com',
            'services.obs.api_key' => 'obs-key-123',
            'services.obs.retry_times' => 1,
            'services.obs.retry_sleep_ms' => 0,
        ]);

        Http::fake([
            'https://obs.example.com/logs' => Http::response([], 202),
        ]);

        $job = new SendObsTelemetryJob([
            'service' => 'xcrawlerii',
            'env' => 'testing',
            'level' => 'INFO',
            'message' => 'queued event',
            'timestamp' => now('UTC')->toIso8601String(),
            'eventType' => 'queue.job.completed',
        ]);

        $job->handle(app(ObservabilityClientInterface::class));

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://obs.example.com/logs'
                && $request->hasHeader('x-api-key', 'obs-key-123')
                && $request->method() === 'POST';
        });
    }

    public function test_emitter_noops_when_obs_is_disabled(): void
    {
        Queue::fake();

        config([
            'services.obs.enabled' => false,
            'services.obs.queue_enabled' => true,
        ]);

        app(TelemetryEmitterInterface::class)->emit('queue.job.started', ['job_name' => 'DemoJob']);

        Queue::assertNothingPushed();
    }

    public function test_emitter_uses_direct_send_when_queue_disabled(): void
    {
        Queue::fake();

        config([
            'services.obs.enabled' => true,
            'services.obs.queue_enabled' => false,
            'services.obs.service_name' => 'xcrawlerii',
            'app.env' => 'testing',
        ]);

        $client = \Mockery::mock(ObservabilityClientInterface::class);
        $client->shouldReceive('sendLog')
            ->once()
            ->with(\Mockery::on(function (array $payload): bool {
                return ($payload['eventType'] ?? null) === 'queue.job.started'
                    && ($payload['env'] ?? null) === 'testing';
            }));

        $this->app->instance(ObservabilityClientInterface::class, $client);
        $this->app->forgetInstance(TelemetryEmitterInterface::class);
        $this->app->singleton(TelemetryEmitterInterface::class, TelemetryEmitter::class);

        app(TelemetryEmitterInterface::class)->emit('queue.job.started', ['job_name' => 'DemoJob']);

        Queue::assertNothingPushed();
    }

    public function test_emitter_swallows_client_exception_in_direct_mode(): void
    {
        config([
            'services.obs.enabled' => true,
            'services.obs.queue_enabled' => false,
        ]);

        $client = \Mockery::mock(ObservabilityClientInterface::class);
        $client->shouldReceive('sendLog')
            ->once()
            ->andThrow(new \RuntimeException('Simulated OBS failure'));

        $this->app->instance(ObservabilityClientInterface::class, $client);
        $this->app->forgetInstance(TelemetryEmitterInterface::class);
        $this->app->singleton(TelemetryEmitterInterface::class, TelemetryEmitter::class);

        app(TelemetryEmitterInterface::class)->emit('queue.job.started', ['job_name' => 'DemoJob']);
    }
}
