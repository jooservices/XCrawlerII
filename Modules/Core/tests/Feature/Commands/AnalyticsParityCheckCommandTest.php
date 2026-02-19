<?php

namespace Modules\Core\Tests\Feature\Commands;

use Modules\Core\Services\AnalyticsParityService;
use Modules\Core\Tests\TestCase;

class AnalyticsParityCheckCommandTest extends TestCase
{
    public function test_command_returns_success_when_no_mismatch(): void
    {
        $service = \Mockery::mock(AnalyticsParityService::class);
        $service->shouldReceive('check')->once()->with(100)->andReturn([
            'checked' => 10,
            'mismatches' => 0,
            'rows' => [],
        ]);

        $this->app->instance(AnalyticsParityService::class, $service);

        $this->artisan('analytics:parity-check')
            ->expectsOutput('Checked: 10, Mismatches: 0')
            ->assertExitCode(0);
    }

    public function test_command_returns_failure_and_prints_rows_when_mismatch_exists(): void
    {
        $service = \Mockery::mock(AnalyticsParityService::class);
        $service->shouldReceive('check')->once()->with(5)->andReturn([
            'checked' => 5,
            'mismatches' => 1,
            'rows' => [[
                'code' => 'ABC-001',
                'mysql_views' => 10,
                'mysql_downloads' => 3,
                'mongo_views' => 9,
                'mongo_downloads' => 3,
            ]],
        ]);

        $this->app->instance(AnalyticsParityService::class, $service);

        $this->artisan('analytics:parity-check', ['--limit' => 5])
            ->expectsOutput('ABC-001: MySQL(10/3) vs Mongo(9/3)')
            ->expectsOutput('Checked: 5, Mismatches: 1')
            ->assertExitCode(1);
    }
}
