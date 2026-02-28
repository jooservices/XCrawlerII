<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature\Events;

use Carbon\CarbonImmutable;
use Modules\Core\Dto\Events\ActorContext;
use Modules\Core\Enums\Events\ActorType;
use Modules\Core\Models\MongoDb\EventLog;
use Modules\Core\Models\MongoDb\EventStore;
use Modules\Core\Services\Events\EventService;
use Modules\Core\Tests\Stubs\Events\StubEventLog;
use Modules\Core\Tests\Stubs\Events\StubEventSourcing;
use Modules\Core\Tests\TestCase;

/**
 * Full flow tests: EventService -> Repository -> Model -> MongoDB. No mocks of internal services.
 */
final class EventServiceFlowTest extends TestCase
{
    private EventService $eventService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertMongoAvailable();
        $this->eventService = $this->app->make(EventService::class);
        $this->cleanCollections();
    }

    protected function tearDown(): void
    {
        $this->cleanCollections();
        parent::tearDown();
    }

    public function test_happy_full_flow_record_sourcing_writes_to_mongo(): void
    {
        $eventId = 'evt-' . fake()->uuid();
        $aggregateId = fake()->uuid();
        $event = new StubEventSourcing(
            eventId: $eventId,
            eventName: 'order.created',
            occurredAt: CarbonImmutable::now(),
            aggregateType: 'order',
            aggregateId: $aggregateId,
            aggregateVersion: 1,
            payload: ['total' => 99.99, 'currency' => 'USD'],
            correlationId: 'req-' . fake()->uuid(),
            actorType: 'user',
            actorId: fake()->uuid(),
        );

        $this->eventService->recordSourcing($event);

        $doc = EventStore::query()->where('event_id', $eventId)->first();
        $this->assertNotNull($doc);
        $this->assertSame('order.created', $doc->event_name);
        $this->assertSame('order', $doc->aggregate_type);
        $this->assertSame($aggregateId, $doc->aggregate_id);
        $this->assertSame(1, $doc->aggregate_version);
        $this->assertSame(99.99, $doc->payload['total']);
        $this->assertNotNull($doc->created_at);
    }

    public function test_happy_full_flow_record_log_writes_to_mongo(): void
    {
        $eventId = 'log-' . fake()->uuid();
        $entityId = fake()->uuid();
        $event = new StubEventLog(
            eventId: $eventId,
            eventName: 'order.updated',
            occurredAt: CarbonImmutable::now(),
            entityType: 'order',
            entityId: $entityId,
            changedFields: ['status', 'paid_at'],
            previous: ['status' => 'pending', 'paid_at' => null],
            new: ['status' => 'paid', 'paid_at' => CarbonImmutable::now()->toIso8601String()],
            correlationId: 'req-' . fake()->uuid(),
            actorType: 'user',
            actorId: fake()->uuid(),
        );

        $this->eventService->recordLog($event);

        $doc = EventLog::query()->where('event_id', $eventId)->first();
        $this->assertNotNull($doc);
        $this->assertSame('order.updated', $doc->event_name);
        $this->assertSame('order', $doc->entity_type);
        $this->assertSame($entityId, $doc->entity_id);
        $this->assertSame(['status', 'paid_at'], $doc->changed_fields);
        $this->assertSame('pending', $doc->previous['status']);
        $this->assertSame('paid', $doc->new['status']);
        $this->assertNotNull($doc->created_at);
    }

    public function test_edge_full_flow_actor_context_overrides_event_actor(): void
    {
        $event = new StubEventSourcing(
            eventId: fake()->uuid(),
            eventName: 'audit.test',
            occurredAt: CarbonImmutable::now(),
            aggregateType: 'audit',
            aggregateId: fake()->uuid(),
            aggregateVersion: null,
            payload: [],
            actorType: null,
            actorId: null,
        );

        $actor = new ActorContext(ActorType::Api, 'api-key-1', 'corr-xyz');
        $this->eventService->recordSourcing($event, $actor);

        $doc = EventStore::query()->where('event_name', 'audit.test')->first();
        $this->assertNotNull($doc);
        $this->assertSame('api', $doc->actor_type);
        $this->assertSame('api-key-1', $doc->actor_id);
        $this->assertSame('corr-xyz', $doc->correlation_id);
    }

    public function test_security_full_flow_sanitizer_redacts_secrets_in_stored_payload(): void
    {
        $event = new StubEventSourcing(
            eventId: fake()->uuid(),
            eventName: 'user.login',
            occurredAt: CarbonImmutable::now(),
            aggregateType: 'user',
            aggregateId: fake()->uuid(),
            aggregateVersion: null,
            payload: [
                'email' => 'u@test.com',
                'password' => 'should-not-appear',
                'api_key' => 'sk-secret',
            ],
        );

        $this->eventService->recordSourcing($event);

        $doc = EventStore::query()->where('event_name', 'user.login')->first();
        $this->assertNotNull($doc);
        $this->assertSame('u@test.com', $doc->payload['email']);
        $this->assertSame('[REDACTED]', $doc->payload['password']);
        $this->assertSame('[REDACTED]', $doc->payload['api_key']);
    }

    private function assertMongoAvailable(): void
    {
        try {
            EventStore::query()->limit(1)->get();
        } catch (\Throwable $e) {
            $this->markTestSkipped('MongoDB is not reachable: ' . $e->getMessage());
        }
    }

    private function cleanCollections(): void
    {
        try {
            EventStore::query()->delete();
            EventLog::query()->delete();
        } catch (\Throwable) {
            // ignore
        }
    }
}
