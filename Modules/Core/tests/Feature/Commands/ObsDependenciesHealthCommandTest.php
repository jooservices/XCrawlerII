<?php

namespace Modules\Core\Tests\Feature\Commands;

use Modules\Core\Observability\Contracts\TelemetryEmitterInterface;
use Modules\Core\Services\DependencyHealthService;
use Tests\TestCase;

class ObsDependenciesHealthCommandTest extends TestCase
{
    public function test_command_emits_dependency_health_events_and_exits_success(): void
    {
        $service = \Mockery::mock(DependencyHealthService::class);
        $service->shouldReceive('collect')
            ->once()
            ->with(['elasticsearch'])
            ->andReturn([
                'elasticsearch' => [
                    'dependency' => 'elasticsearch',
                    'status' => 'up',
                    'latency_ms' => 12,
                    'error' => null,
                    'checked_at' => now('UTC')->toIso8601String(),
                ],
            ]);

        $emitter = \Mockery::mock(TelemetryEmitterInterface::class);
        $emitter->shouldReceive('emit')
            ->once()
            ->withArgs(function (string $eventType, array $context, string $level, string $message): bool {
                return $eventType === 'dependency.health'
                    && ($context['dependency'] ?? null) === 'elasticsearch'
                    && ($context['status'] ?? null) === 'up'
                    && $level === 'info'
                    && $message === 'Dependency health probe executed';
            });

        $this->app->instance(DependencyHealthService::class, $service);
        $this->app->instance(TelemetryEmitterInterface::class, $emitter);

        $this->artisan('obs:dependencies-health', [
            '--only' => 'elasticsearch',
        ])->assertExitCode(0);
    }

    public function test_command_fails_when_fail_on_down_is_enabled_and_dependency_is_down(): void
    {
        $service = \Mockery::mock(DependencyHealthService::class);
        $service->shouldReceive('collect')
            ->once()
            ->with([])
            ->andReturn([
                'redis' => [
                    'dependency' => 'redis',
                    'status' => 'down',
                    'latency_ms' => 20,
                    'error' => 'Connection refused',
                    'checked_at' => now('UTC')->toIso8601String(),
                ],
            ]);

        $emitter = \Mockery::mock(TelemetryEmitterInterface::class);
        $emitter->shouldReceive('emit')->once();

        $this->app->instance(DependencyHealthService::class, $service);
        $this->app->instance(TelemetryEmitterInterface::class, $emitter);

        $this->artisan('obs:dependencies-health', [
            '--fail-on-down' => true,
        ])->assertExitCode(1);
    }
}
