<?php

namespace Modules\Core\Tests\Unit\Observability;

use Illuminate\Support\Facades\Log;
use Modules\Core\Jobs\SendObsTelemetryJob;
use Modules\Core\Observability\Contracts\ObservabilityClientInterface;
use Modules\Core\Observability\Exceptions\ObsConfigurationException;
use Modules\Core\Observability\Exceptions\ObsNonRetryableException;
use Tests\TestCase;

class SendObsTelemetryJobTest extends TestCase
{
    private const FAILURE_LOG_MESSAGE = 'OBS telemetry delivery permanently failed';

    public function test_failed_logs_to_single_channel_for_fallback(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('single')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === self::FAILURE_LOG_MESSAGE
                    && ($context['event_type'] ?? null) === 'queue.job.failed'
                    && ($context['obs_failure_type'] ?? null) === 'unknown'
                    && ($context['metric_key'] ?? null) === 'obs_delivery_failure_total'
                    && ($context['__obs_skip'] ?? null) === true;
            });

        $job = new SendObsTelemetryJob([
            'eventType' => 'queue.job.failed',
        ]);

        $job->failed(new \RuntimeException('simulated failure'));
    }

    public function test_handle_swallows_non_retryable_exception_and_logs_fallback(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('single')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === self::FAILURE_LOG_MESSAGE
                    && ($context['event_type'] ?? null) === 'queue.job.failed'
                    && ($context['obs_failure_type'] ?? null) === 'non_retryable';
            });

        $client = \Mockery::mock(ObservabilityClientInterface::class);
        $client->shouldReceive('sendLog')
            ->once()
            ->andThrow(new ObsNonRetryableException('OBS rejected payload'));

        $job = new SendObsTelemetryJob([
            'eventType' => 'queue.job.failed',
        ]);

        $job->handle($client);

        $this->assertTrue(true);
    }

    public function test_handle_swallows_configuration_exception_and_logs_fallback(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('single')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === self::FAILURE_LOG_MESSAGE
                    && ($context['event_type'] ?? null) === 'queue.job.failed'
                    && ($context['obs_failure_type'] ?? null) === 'configuration';
            });

        $client = \Mockery::mock(ObservabilityClientInterface::class);
        $client->shouldReceive('sendLog')
            ->once()
            ->andThrow(new ObsConfigurationException('OBS missing config'));

        $job = new SendObsTelemetryJob([
            'eventType' => 'queue.job.failed',
        ]);

        $job->handle($client);

        $this->assertTrue(true);
    }
}
