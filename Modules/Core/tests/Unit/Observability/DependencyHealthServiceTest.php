<?php

namespace Modules\Core\Tests\Unit\Observability;

use Illuminate\Support\Facades\Http;
use Modules\Core\Services\DependencyHealthService;
use Tests\TestCase;

class DependencyHealthServiceTest extends TestCase
{
    public function test_collect_reports_elasticsearch_up_when_probe_succeeds(): void
    {
        config([
            'scout.elasticsearch.hosts.0' => 'http://elasticsearch:9200',
        ]);

        Http::fake([
            'http://elasticsearch:9200/' => Http::response(['cluster_name' => 'test'], 200),
        ]);

        $service = app(DependencyHealthService::class);
        $report = $service->collect(['elasticsearch']);

        $this->assertSame('up', $report['elasticsearch']['status']);
        $this->assertIsInt($report['elasticsearch']['latency_ms']);
        $this->assertNull($report['elasticsearch']['error']);
    }

    public function test_collect_reports_elasticsearch_down_when_probe_fails(): void
    {
        config([
            'scout.elasticsearch.hosts.0' => 'http://elasticsearch:9200',
        ]);

        Http::fake([
            'http://elasticsearch:9200/' => Http::response(['error' => 'down'], 503),
        ]);

        $service = app(DependencyHealthService::class);
        $report = $service->collect(['elasticsearch']);

        $this->assertSame('down', $report['elasticsearch']['status']);
        $this->assertIsString($report['elasticsearch']['error']);
    }

    public function test_collect_reports_unknown_status_for_unsupported_dependency(): void
    {
        $service = app(DependencyHealthService::class);
        $report = $service->collect(['weird-target']);

        $this->assertSame('unknown', $report['weird-target']['status']);
        $this->assertSame('Unsupported dependency target', $report['weird-target']['error']);
    }
}
