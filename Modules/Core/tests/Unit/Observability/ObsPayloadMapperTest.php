<?php

namespace Modules\Core\Tests\Unit\Observability;

use Modules\Core\Observability\ObsPayloadMapper;
use Tests\TestCase;

class ObsPayloadMapperTest extends TestCase
{
    public function test_it_maps_payload_using_app_env_and_redacts_sensitive_values(): void
    {
        config([
            'app.env' => 'staging',
            'services.obs.service_name' => 'xcrawlerii',
            'services.obs.redact_keys' => ['password', 'token'],
        ]);

        $mapper = app(ObsPayloadMapper::class);

        $payload = $mapper->map('queue.job.failed', [
            'password' => 'secret',
            'nested' => ['token' => 'abc123'],
            'traceId' => 'trace-1',
        ], 'error', 'Queue failed');

        $this->assertSame('xcrawlerii', $payload['service']);
        $this->assertSame('staging', $payload['env']);
        $this->assertSame('ERROR', $payload['level']);
        $this->assertSame('trace-1', $payload['traceId']);
        $this->assertSame('[REDACTED]', $payload['context']['password']);
        $this->assertSame('[REDACTED]', $payload['context']['nested']['token']);
        $this->assertContains('queue.job.failed', $payload['tags']);
    }

    public function test_it_uses_trace_id_fallback_and_normalizes_tags(): void
    {
        config([
            'app.env' => 'production',
            'services.obs.service_name' => 'xcrawlerii',
            'services.obs.redact_keys' => [],
        ]);

        $mapper = app(ObsPayloadMapper::class);

        $payload = $mapper->map('queue.job.completed', [
            'trace_id' => 'trace-fallback',
            'tags' => ['crawler', 123, '', 'crawler'],
        ]);

        $this->assertSame('trace-fallback', $payload['traceId']);
        $this->assertContains('crawler', $payload['tags']);
        $this->assertContains('queue.job.completed', $payload['tags']);
        $this->assertNotContains(123, $payload['tags']);
    }

    public function test_it_falls_back_to_default_message_and_timestamp_for_invalid_input(): void
    {
        config([
            'app.env' => 'local',
            'services.obs.service_name' => 'xcrawlerii',
            'services.obs.redact_keys' => [],
        ]);

        $mapper = app(ObsPayloadMapper::class);

        $payload = $mapper->map('app.log', [
            'timestamp' => 12345,
        ], 'warning', null);

        $this->assertSame('WARNING', $payload['level']);
        $this->assertSame('Operational event: app.log', $payload['message']);
        $this->assertIsString($payload['timestamp']);
        $this->assertSame('local', $payload['env']);
    }

    public function test_it_truncates_context_when_payload_exceeds_max_size(): void
    {
        config([
            'app.env' => 'local',
            'services.obs.service_name' => 'xcrawlerii',
            'services.obs.redact_keys' => [],
            'services.obs.max_payload_bytes' => 1024,
        ]);

        $mapper = app(ObsPayloadMapper::class);

        $payload = $mapper->map('app.log', [
            'huge' => str_repeat('x', 20000),
        ], 'info', 'Huge payload test');

        $this->assertTrue((bool) ($payload['context']['_truncated'] ?? false));
        $this->assertSame('payload_too_large', $payload['context']['_reason'] ?? null);
        $this->assertStringContainsString('context_truncated', $payload['message']);
    }
}
