<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Mockery;
use Modules\JAV\Services\AnalyticsSnapshotService;
use Modules\JAV\Tests\TestCase;

class JavSyncAnalyticsCommandTest extends TestCase
{
    public function test_command_rejects_invalid_days_input(): void
    {
        $service = Mockery::mock(AnalyticsSnapshotService::class);
        $service->shouldNotReceive('getSnapshot');
        $this->app->instance(AnalyticsSnapshotService::class, $service);

        $this->artisan('jav:sync:analytics', [
            '--days' => [2, 99],
        ])->assertExitCode(2);
    }

    public function test_command_syncs_selected_days(): void
    {
        $service = Mockery::mock(AnalyticsSnapshotService::class);
        $service->shouldReceive('getSnapshot')->once()->with(7, true, false)->andReturn([
            'totals' => ['jav' => 10, 'actors' => 20, 'tags' => 30],
        ]);
        $service->shouldReceive('getSnapshot')->once()->with(30, true, false)->andReturn([
            'totals' => ['jav' => 11, 'actors' => 21, 'tags' => 31],
        ]);
        $this->app->instance(AnalyticsSnapshotService::class, $service);

        $this->artisan('jav:sync:analytics', [
            '--days' => [7, 30],
        ])->assertExitCode(0);
    }

    public function test_command_returns_failure_when_snapshot_service_throws(): void
    {
        $service = Mockery::mock(AnalyticsSnapshotService::class);
        $service->shouldReceive('getSnapshot')
            ->once()
            ->with(7, true, false)
            ->andThrow(new \RuntimeException('Mongo unavailable'));
        $this->app->instance(AnalyticsSnapshotService::class, $service);

        $this->artisan('jav:sync:analytics', [
            '--days' => [7],
        ])->assertExitCode(1);
    }
}
