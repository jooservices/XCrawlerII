<?php

namespace Modules\Core\Tests\Feature\Commands;

use Mockery;
use Modules\Core\Services\AnalyticsFlushService;
use Modules\Core\Tests\TestCase;

class FlushAnalyticsCommandTest extends TestCase
{
    public function test_returns_success_when_no_errors(): void
    {
        $mock = Mockery::mock(AnalyticsFlushService::class);
        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(['keys_processed' => 10, 'errors' => 0]);
        $this->app->instance(AnalyticsFlushService::class, $mock);

        $this->artisan('analytics:flush')
            ->expectsOutputToContain('Flushed 10 keys, 0 errors')
            ->assertSuccessful();
    }

    public function test_returns_failure_when_errors_exist(): void
    {
        $mock = Mockery::mock(AnalyticsFlushService::class);
        $mock->shouldReceive('flush')
            ->once()
            ->andReturn(['keys_processed' => 5, 'errors' => 3]);
        $this->app->instance(AnalyticsFlushService::class, $mock);

        $this->artisan('analytics:flush')
            ->expectsOutputToContain('Flushed 5 keys, 3 errors')
            ->assertFailed();
    }

    public function test_handles_empty_result_array_gracefully(): void
    {
        $mock = Mockery::mock(AnalyticsFlushService::class);
        $mock->shouldReceive('flush')
            ->once()
            ->andReturn([]);
        $this->app->instance(AnalyticsFlushService::class, $mock);

        $this->artisan('analytics:flush')
            ->expectsOutputToContain('Flushed 0 keys, 0 errors')
            ->assertSuccessful();
    }
}
