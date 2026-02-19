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
        Redis::shouldReceive('setnx')
            ->once()
            ->withArgs(fn ($key) => str_starts_with($key, 'anl:evt:'))
            ->andReturn(true);
        Redis::shouldReceive('expire')->once();

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
        Redis::shouldReceive('setnx')->andReturn(true);
        Redis::shouldReceive('expire')->once();

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
        Redis::shouldReceive('setnx')
            ->once()
            ->with("anl:evt:{$eventId}", 1)
            ->andReturn(true);
        Redis::shouldReceive('expire')->once()->with("anl:evt:{$eventId}", 172800);

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
        Redis::shouldReceive('setnx')
            ->once()
            ->with("anl:evt:{$eventId}", 1)
            ->andReturn(false); // Key exists

        Redis::shouldReceive('expire')->never();
        Redis::shouldReceive('hincrby')->never(); // No write

        $this->postJson(route('api.analytics.events.store'), $payload)->assertStatus(202);
    }

    public function test_redis_connection_failure_handled_gracefully(): void
    {
        Redis::shouldReceive('setnx')->andThrow(new \Exception('Redis connection refused'));

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
            'invalid domain' => [array_merge($base, ['domain' => 'bad']), 'domain'],
            'invalid entity_type' => [array_merge($base, ['entity_type' => 'user']), 'entity_type'],
            'invalid action' => [array_merge($base, ['action' => 'hack']), 'action'],
            'invalid date' => [array_merge($base, ['occurred_at' => 'not-a-date']), 'occurred_at'],
            'value too low' => [array_merge($base, ['value' => 0]), 'value'],
            'value too high' => [array_merge($base, ['value' => 101]), 'value'],
            'value non-int' => [array_merge($base, ['value' => 'one']), 'value'],
        ];
    }
}
