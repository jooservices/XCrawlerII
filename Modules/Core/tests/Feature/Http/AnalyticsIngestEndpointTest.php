<?php

namespace Modules\Core\Tests\Feature\Http;

use Faker\Factory as FakerFactory;
use Illuminate\Support\Facades\Redis;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AnalyticsIngestEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_valid_event_writes_to_redis_correctly(): void
    {
        $eventId = $this->faker->uuid();
        $entityId = $this->faker->uuid();
        Redis::shouldReceive('set')
            ->once()
            ->withArgs(function ($key, $value, $exKeyword, $ttl, $nxKeyword) {
                return str_starts_with($key, 'anl:evt:')
                    && $value === '1'
                    && $exKeyword === 'EX'
                    && $ttl === 172800
                    && $nxKeyword === 'NX';
            })
            ->andReturn('OK');

        Redis::shouldReceive('hincrby')->twice();

        $payload = [
            'event_id' => $eventId,
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $entityId,
            'action' => AnalyticsAction::View->value,
            'value' => 1,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(202)
            ->assertJson(['status' => 'accepted']);
    }

    #[DataProvider('entityTypeProvider')]
    public function test_all_entity_types_generate_correct_redis_keys(string $entityType): void
    {
        $eventId = $this->faker->uuid();
        $entityId = $this->faker->uuid();
        Redis::shouldReceive('set')->andReturn('OK');

        // Expect key: anl:counters:jav:{entityType}:{uuid}
        $expectedKey = 'anl:counters:'.AnalyticsDomain::Jav->value.":{$entityType}:{$entityId}";

        Redis::shouldReceive('hincrby')
            ->once()
            ->with($expectedKey, AnalyticsAction::View->value, 1);

        Redis::shouldReceive('hincrby')
            ->once()
            ->with($expectedKey, AnalyticsAction::View->value.':2026-02-19', 1);

        $payload = [
            'event_id' => $eventId,
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)->assertStatus(202);
    }

    public static function entityTypeProvider(): array
    {
        return [
            [AnalyticsEntityType::Movie->value],
            [AnalyticsEntityType::Actor->value],
            [AnalyticsEntityType::Tag->value],
        ];
    }

    public function test_duplicate_event_id_ignored(): void
    {
        $eventId = $this->faker->uuid();
        $entityId = $this->faker->uuid();
        // First call: New event
        Redis::shouldReceive('set')
            ->once()
            ->with("anl:evt:{$eventId}", '1', 'EX', 172800, 'NX')
            ->andReturn('OK');

        Redis::shouldReceive('hincrby')->twice();

        $payload = [
            'event_id' => $eventId,
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $entityId,
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)->assertStatus(202);

        // Second call: Duplicate event
        Redis::shouldReceive('set')
            ->once()
            ->with("anl:evt:{$eventId}", '1', 'EX', 172800, 'NX')
            ->andReturn(null); // Key exists

        Redis::shouldReceive('hincrby')->never(); // No write

        $this->postJson(route('api.analytics.events.store'), $payload)->assertStatus(202);
    }

    public function test_redis_connection_failure_handled_gracefully(): void
    {
        Redis::shouldReceive('set')->andThrow(new \Exception('Redis connection refused'));

        $payload = [
            'event_id' => $this->faker->uuid(),
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $this->faker->uuid(),
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        // Should return 500 or handle exception by Laravel handler
        $this->postJson(route('api.analytics.events.store'), $payload)->assertStatus(500);
    }

    public function test_rate_limiting(): void
    {
        // Set low limit for testing
        config(['analytics.rate_limit_per_minute' => 2]);

        // Re-register the limiter to pick up config change
        \Illuminate\Support\Facades\RateLimiter::for('analytics', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(config('analytics.rate_limit_per_minute'))->by($request->ip());
        });

        $payload = [
            'event_id' => $this->faker->uuid(),
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $this->faker->uuid(),
            'action' => AnalyticsAction::View->value,
            'occurred_at' => now()->toIso8601String(),
        ];

        // Mock Redis for success path
        Redis::shouldReceive('set')->andReturn('OK');
        Redis::shouldReceive('hincrby')->andReturn(1);

        // 1st request: OK
        $this->postJson(route('api.analytics.events.store'), $payload)->assertStatus(202);

        // 2nd request: OK
        $this->postJson(route('api.analytics.events.store'), $payload)->assertStatus(202);

        // 3rd request: 429
        $this->postJson(route('api.analytics.events.store'), $payload)->assertStatus(429);
    }

    #[DataProvider('invalidPayloadProvider')]
    public function test_validation_rules(array $payload, string $errorField): void
    {
        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors([$errorField]);
    }

    public static function invalidPayloadProvider(): array
    {
        $faker = FakerFactory::create();
        $base = [
            'event_id' => $faker->uuid(),
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $faker->uuid(),
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        return [
            'missing event_id' => [array_diff_key($base, ['event_id' => '']), 'event_id'],
            'missing domain' => [array_diff_key($base, ['domain' => '']), 'domain'],
            'missing entity_id' => [array_diff_key($base, ['entity_id' => '']), 'entity_id'],
            'invalid domain' => [array_merge($base, ['domain' => 'bad']), 'domain'],
            'entity_id empty' => [array_merge($base, ['entity_id' => '']), 'entity_id'],
            'entity_id too long' => [array_merge($base, ['entity_id' => str_repeat('a', 256)]), 'entity_id'],
            'event_id too long' => [array_merge($base, ['event_id' => str_repeat('b', 256)]), 'event_id'],
            'invalid entity_type' => [array_merge($base, ['entity_type' => 'user']), 'entity_type'],
            'invalid action' => [array_merge($base, ['action' => 'hack']), 'action'],
            'invalid date' => [array_merge($base, ['occurred_at' => 'not-a-date']), 'occurred_at'],
            'value too low' => [array_merge($base, ['value' => 0]), 'value'],
            'value too high' => [array_merge($base, ['value' => 10001]), 'value'],
            'value non-int' => [array_merge($base, ['value' => 'one']), 'value'],
        ];
    }

    public function test_value_one_and_hundred_accepted(): void
    {
        foreach ([1, 100] as $value) {
            $eventId = $this->faker->uuid();
            $entityId = $this->faker->uuid();
            Redis::shouldReceive('set')->andReturn('OK');
            Redis::shouldReceive('hincrby')->twice();

            $payload = [
                'event_id' => $eventId,
                'domain' => AnalyticsDomain::Jav->value,
                'entity_type' => AnalyticsEntityType::Movie->value,
                'entity_id' => $entityId,
                'action' => AnalyticsAction::View->value,
                'value' => $value,
                'occurred_at' => '2026-02-19T10:00:00Z',
            ];

            $this->postJson(route('api.analytics.events.store'), $payload)
                ->assertStatus(202)
                ->assertJson(['status' => 'accepted']);
        }
    }

    public function test_idempotency_same_payload_twice_returns_202_both_times_counted_once(): void
    {
        $eventId = $this->faker->uuid();
        $entityId = $this->faker->uuid();
        $payload = [
            'event_id' => $eventId,
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $entityId,
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        Redis::shouldReceive('set')
            ->once()
            ->with("anl:evt:{$eventId}", '1', 'EX', 172800, 'NX')
            ->andReturn('OK');
        Redis::shouldReceive('hincrby')->twice();

        $this->postJson(route('api.analytics.events.store'), $payload)->assertStatus(202);

        Redis::shouldReceive('set')
            ->once()
            ->with("anl:evt:{$eventId}", '1', 'EX', 172800, 'NX')
            ->andReturn(null);
        Redis::shouldReceive('hincrby')->never();

        $this->postJson(route('api.analytics.events.store'), $payload)->assertStatus(202);
    }

    public function test_guest_receives_202(): void
    {
        $this->assertGuest();
        $eventId = $this->faker->uuid();
        $entityId = $this->faker->uuid();
        Redis::shouldReceive('set')->andReturn('OK');
        Redis::shouldReceive('hincrby')->twice();

        $payload = [
            'event_id' => $eventId,
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $entityId,
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)->assertStatus(202);
    }

    public function test_authenticated_user_receives_202(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        $eventId = $this->faker->uuid();
        $entityId = $this->faker->uuid();
        Redis::shouldReceive('set')->andReturn('OK');
        Redis::shouldReceive('hincrby')->twice();

        $payload = [
            'event_id' => $eventId,
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $entityId,
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)->assertStatus(202);
    }

    // ──────────────────────────────────────────
    // user_id validation (withValidator)
    // ──────────────────────────────────────────

    public function test_user_id_mismatch_returns_422(): void
    {
        $user = \App\Models\User::factory()->create();
        $otherUser = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'event_id' => $this->faker->uuid(),
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $this->faker->uuid(),
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
            'user_id' => $otherUser->id, // mismatch!
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_user_id_matching_auth_user_passes(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        Redis::shouldReceive('set')->andReturn('OK');
        Redis::shouldReceive('hincrby')->twice();

        $payload = [
            'event_id' => $this->faker->uuid(),
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $this->faker->uuid(),
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
            'user_id' => $user->id, // matches auth user
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(202);
    }

    // ──────────────────────────────────────────
    // Security: injection / XSS payloads
    // ──────────────────────────────────────────

    public function test_sql_injection_in_entity_id_passes_validation(): void
    {
        // entity_id is just a string — no SQL query uses it directly
        Redis::shouldReceive('set')->andReturn('OK');
        Redis::shouldReceive('hincrby')->twice();

        $payload = [
            'event_id' => $this->faker->uuid(),
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => "1' OR '1'='1",
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(202);
    }

    public function test_xss_payload_in_event_id_passes_validation(): void
    {
        // event_id is never rendered as HTML — stored as Redis key
        Redis::shouldReceive('set')->andReturn('OK');
        Redis::shouldReceive('hincrby')->twice();

        $payload = [
            'event_id' => '<script>alert(1)</script>',
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $this->faker->uuid(),
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(202);
    }

    // ──────────────────────────────────────────
    // Individual missing required fields
    // ──────────────────────────────────────────

    public function test_missing_action_returns_422(): void
    {
        $payload = [
            'event_id' => $this->faker->uuid(),
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $this->faker->uuid(),
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['action']);
    }

    public function test_missing_entity_type_returns_422(): void
    {
        $payload = [
            'event_id' => $this->faker->uuid(),
            'domain' => AnalyticsDomain::Jav->value,
            'entity_id' => $this->faker->uuid(),
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['entity_type']);
    }

    public function test_missing_occurred_at_returns_422(): void
    {
        $payload = [
            'event_id' => $this->faker->uuid(),
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $this->faker->uuid(),
            'action' => AnalyticsAction::View->value,
        ];

        $this->postJson(route('api.analytics.events.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['occurred_at']);
    }
}
