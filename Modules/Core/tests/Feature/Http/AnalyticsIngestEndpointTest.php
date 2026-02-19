<?php

namespace Modules\Core\Tests\Feature\Http;

use Illuminate\Support\Facades\Redis;
use Modules\Core\Tests\TestCase;

class AnalyticsIngestEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['analytics.enabled' => true]);
    }

    public function test_valid_event_returns_202_and_writes_redis(): void
    {
        Redis::shouldReceive('set')
            ->once()
            ->withArgs(fn ($key, $val, $opt1, $opt2, $ttl) => str_starts_with($key, 'anl:evt:'))
            ->andReturn(true);

        Redis::shouldReceive('hincrby')->twice();

        $payload = [
            'event_id' => 'test-event-1',
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => 'abc-123-uuid',
            'action' => 'view',
            'value' => 1,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(202)
            ->assertJsonPath('status', 'accepted');
    }

    public function test_missing_required_fields_returns_422(): void
    {
        $this->postJson(route('api.analytics.events.store'), [])
            ->assertStatus(422);
    }

    public function test_invalid_action_returns_422(): void
    {
        $payload = [
            'event_id' => 'test',
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => 'abc',
            'action' => 'invalid_action',
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['action']);
    }

    public function test_invalid_domain_returns_422(): void
    {
        $payload = [
            'event_id' => 'test',
            'domain' => 'unknown',
            'entity_type' => 'movie',
            'entity_id' => 'abc',
            'action' => 'view',
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['domain']);
    }

    public function test_feature_flag_off_accepts_but_does_not_write_redis(): void
    {
        config(['analytics.enabled' => false]);
        Redis::shouldReceive('hincrby')->never();

        $payload = [
            'event_id' => 'test-event-1',
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => 'abc-123-uuid',
            'action' => 'view',
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(202)
            ->assertJsonPath('status', 'accepted');
    }

    public function test_user_id_from_payload_is_ignored(): void
    {
        Redis::shouldReceive('set')->andReturn(true);
        Redis::shouldReceive('hincrby')->twice();

        $payload = [
            'event_id' => 'test',
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => 'abc',
            'action' => 'view',
            'user_id' => 999,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(202)
            ->assertJsonPath('status', 'accepted');
    }

    public function test_same_event_id_is_deduplicated(): void
    {
        Redis::shouldReceive('set')
            ->once()
            ->with('anl:evt:test-duplicate-id', 1, 'NX', 'EX', 172800)
            ->andReturn(true);

        Redis::shouldReceive('hincrby')->twice(); // First time writes

        $payload = [
            'event_id' => 'test-duplicate-id',
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => 'abc',
            'action' => 'view',
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(202);

        // Second request
        Redis::shouldReceive('set')
            ->once()
            ->with('anl:evt:test-duplicate-id', 1, 'NX', 'EX', 172800)
            ->andReturn(false); // Valid key exists

        Redis::shouldReceive('hincrby')->never(); // Should NOT write counters

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(202);
    }

    public function test_redis_key_structure_and_date_bucket(): void
    {
        Redis::shouldReceive('set')->andReturn(true);

        // Expect exact key structure
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:jav:movie:abc-123', 'view', 1);

        // Expect date bucket
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:jav:movie:abc-123', 'view:2026-02-19', 1);

        $payload = [
            'event_id' => 'test-key-structure',
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => 'abc-123',
            'action' => 'view',
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(202);
    }

    public function test_value_min_max_validation(): void
    {
        $basePayload = [
            'event_id' => 'test',
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => 'abc',
            'action' => 'view',
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        // Min value -1
        $this->postJson(route('api.analytics.events.store'), array_merge($basePayload, ['value' => 0]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['value']);

        // Max value 101
        $this->postJson(route('api.analytics.events.store'), array_merge($basePayload, ['value' => 101]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['value']);

        // Non-integer
        $this->postJson(route('api.analytics.events.store'), array_merge($basePayload, ['value' => 'string']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['value']);
    }

    public function test_occurred_at_format_validation(): void
    {
        $payload = [
            'event_id' => 'test',
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => 'abc',
            'action' => 'view',
            'occurred_at' => '2026-02-19 10:00:00',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['occurred_at']);
    }
}
