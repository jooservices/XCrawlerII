<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature\Console;

use Mockery;
use Modules\Core\Services\Health;
use Modules\Core\Tests\TestCase;

final class ServicesHealthCheckTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_command_returns_success_when_all_services_are_healthy(): void
    {
        $checks = [
            ['service' => 'mariadb', 'status' => 'OK', 'detail' => 'connection ok'],
            ['service' => 'mongodb', 'status' => 'OK', 'detail' => 'ping ok'],
            ['service' => 'redis', 'status' => 'OK', 'detail' => 'ping PONG'],
            ['service' => 'elasticsearch', 'status' => 'OK', 'detail' => 'cluster xcrawler'],
        ];

        $this->mockHealthResult($checks, true);

        $this->artisan('services:health')
            ->expectsTable(['service', 'status', 'detail'], $checks)
            ->assertExitCode(0);
    }

    public function test_command_returns_failure_when_one_service_is_unhealthy(): void
    {
        $checks = [
            ['service' => 'mariadb', 'status' => 'OK', 'detail' => 'connection ok'],
            ['service' => 'mongodb', 'status' => 'OK', 'detail' => 'ping ok'],
            ['service' => 'redis', 'status' => 'FAIL', 'detail' => 'Connection refused'],
            ['service' => 'elasticsearch', 'status' => 'OK', 'detail' => 'cluster xcrawler'],
        ];

        $this->mockHealthResult($checks, false);

        $this->artisan('services:health')
            ->expectsTable(['service', 'status', 'detail'], $checks)
            ->assertExitCode(1);
    }

    public function test_command_returns_failure_when_elasticsearch_url_is_missing(): void
    {
        $checks = [
            ['service' => 'mariadb', 'status' => 'OK', 'detail' => 'connection ok'],
            ['service' => 'mongodb', 'status' => 'OK', 'detail' => 'ping ok'],
            ['service' => 'redis', 'status' => 'OK', 'detail' => 'ping PONG'],
            ['service' => 'elasticsearch', 'status' => 'FAIL', 'detail' => 'missing ELASTICSEARCH_URL'],
        ];

        $this->mockHealthResult($checks, false);

        $this->artisan('services:health')
            ->expectsTable(['service', 'status', 'detail'], $checks)
            ->assertExitCode(1);
    }

    public function test_command_reports_all_services_when_mixed_results_exist(): void
    {
        $checks = [
            ['service' => 'mariadb', 'status' => 'FAIL', 'detail' => 'SQLSTATE[HY000] [2002] Connection refused'],
            ['service' => 'mongodb', 'status' => 'OK', 'detail' => 'ping ok'],
            ['service' => 'redis', 'status' => 'FAIL', 'detail' => 'Connection refused'],
            ['service' => 'elasticsearch', 'status' => 'FAIL', 'detail' => 'http 503'],
        ];

        $this->mockHealthResult($checks, false);

        $this->artisan('services:health')
            ->expectsTable(['service', 'status', 'detail'], $checks)
            ->assertExitCode(1);
    }

    /**
     * @param  array<int, array{service: string, status: string, detail: string}>  $checks
     */
    private function mockHealthResult(array $checks, bool $healthy): void
    {
        $health = Mockery::mock(Health::class);
        $health->shouldReceive('check')
            ->once()
            ->andReturn([
                'healthy' => $healthy,
                'checks' => $checks,
            ]);

        $this->app->instance(Health::class, $health);
    }
}
