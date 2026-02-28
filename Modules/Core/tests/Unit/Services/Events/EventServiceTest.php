<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Services\Events;

use Carbon\CarbonImmutable;
use Modules\Core\Dto\Events\ActorContext;
use Modules\Core\Enums\Events\ActorType;
use Modules\Core\Models\MongoDb\EventLog;
use Modules\Core\Models\MongoDb\EventStore;
use Modules\Core\Services\Events\EventService;
use Modules\Core\Tests\Stubs\Events\StubEventLog;
use Modules\Core\Tests\Stubs\Events\StubEventSourcing;
use Modules\Core\Tests\TestCase;

final class EventServiceTest extends TestCase
{
    private EventService $eventService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventService = $this->app->make(EventService::class);
        $this->cleanEventCollections();
    }

    protected function tearDown(): void
    {
        $this->cleanEventCollections();
        parent::tearDown();
    }

    public function test_record_sourcing_persists_document_with_required_fields(): void
    {
        $event = new StubEventSourcing(
            eventId: 'evt-'.fake()->uuid(),
            eventName: 'test.aggregate.created',
            occurredAt: CarbonImmutable::now(),
            aggregateType: 'test_aggregate',
            aggregateId: fake()->uuid(),
            aggregateVersion: 1,
            payload: ['title' => fake()->sentence()],
            correlationId: fake()->uuid(),
            causationId: null,
            actorType: 'user',
            actorId: fake()->uuid(),
        );

        $this->eventService->recordSourcing($event);

        $this->assertDatabaseHas(EventStore::COLLECTION, [
            'event_id' => $event->getEventId(),
            'event_name' => 'test.aggregate.created',
            'aggregate_type' => 'test_aggregate',
            'aggregate_id' => $event->getAggregateId(),
        ], 'mongodb');
    }

    public function test_record_sourcing_generates_ulid_when_event_id_null(): void
    {
        $event = new StubEventSourcing(
            eventId: null,
            eventName: 'test.created',
            occurredAt: CarbonImmutable::now(),
            aggregateType: 'agg',
            aggregateId: 'id-1',
            aggregateVersion: null,
            payload: [],
        );

        $this->eventService->recordSourcing($event);

        $doc = EventStore::query()->where('event_name', 'test.created')->first();
        $this->assertNotNull($doc);
        $this->assertMatchesRegularExpression('/^[0-9A-HJ-NP-Za-km-z]{26}$/', (string) $doc->event_id);
    }

    public function test_record_sourcing_applies_actor_context_override(): void
    {
        $event = new StubEventSourcing(
            eventId: fake()->uuid(),
            eventName: 'test.updated',
            occurredAt: CarbonImmutable::now(),
            aggregateType: 'agg',
            aggregateId: 'id-1',
            aggregateVersion: null,
            payload: [],
            actorType: 'system',
            actorId: null,
        );

        $actor = new ActorContext(ActorType::User, 'user-123', 'corr-456');
        $this->eventService->recordSourcing($event, $actor);

        $doc = EventStore::query()->where('event_name', 'test.updated')->first();
        $this->assertNotNull($doc);
        $this->assertSame('user', $doc->actor_type);
        $this->assertSame('user-123', $doc->actor_id);
        $this->assertSame('corr-456', $doc->correlation_id);
    }

    public function test_record_sourcing_sanitizes_payload_secrets(): void
    {
        $event = new StubEventSourcing(
            eventId: fake()->uuid(),
            eventName: 'user.registered',
            occurredAt: CarbonImmutable::now(),
            aggregateType: 'user',
            aggregateId: fake()->uuid(),
            aggregateVersion: null,
            payload: [
                'email' => 'u@example.com',
                'password' => 'secret123',
                'token' => 'abc',
            ],
        );

        $this->eventService->recordSourcing($event);

        $doc = EventStore::query()->where('event_name', 'user.registered')->first();
        $this->assertNotNull($doc);
        $this->assertSame('u@example.com', $doc->payload['email']);
        $this->assertSame('[REDACTED]', $doc->payload['password']);
        $this->assertSame('[REDACTED]', $doc->payload['token']);
    }

    public function test_record_log_persists_document_with_changed_fields(): void
    {
        $event = new StubEventLog(
            eventId: 'log-'.fake()->uuid(),
            eventName: 'entity.updated',
            occurredAt: CarbonImmutable::now(),
            entityType: 'order',
            entityId: fake()->uuid(),
            changedFields: ['status', 'amount'],
            previous: ['status' => 'pending', 'amount' => 100],
            new: ['status' => 'paid', 'amount' => 100],
            correlationId: null,
            actorType: 'user',
            actorId: fake()->uuid(),
        );

        $this->eventService->recordLog($event);

        $this->assertDatabaseHas(EventLog::COLLECTION, [
            'event_id' => $event->getEventId(),
            'event_name' => 'entity.updated',
            'entity_type' => 'order',
            'entity_id' => $event->getEntityId(),
        ], 'mongodb');
    }

    public function test_record_log_applies_actor_context(): void
    {
        $event = new StubEventLog(
            eventId: null,
            eventName: 'entity.updated',
            occurredAt: CarbonImmutable::now(),
            entityType: 'item',
            entityId: 'item-1',
            changedFields: ['name'],
            previous: ['name' => 'Old'],
            new: ['name' => 'New'],
        );

        $actor = ActorContext::user('user-99', 'req-1');
        $this->eventService->recordLog($event, $actor);

        $doc = EventLog::query()->where('entity_id', 'item-1')->first();
        $this->assertNotNull($doc);
        $this->assertSame('user', $doc->actor_type);
        $this->assertSame('user-99', $doc->actor_id);
        $this->assertSame('req-1', $doc->correlation_id);
    }

    public function test_record_log_sanitizes_previous_and_new(): void
    {
        $event = new StubEventLog(
            eventId: fake()->uuid(),
            eventName: 'user.updated',
            occurredAt: CarbonImmutable::now(),
            entityType: 'user',
            entityId: 'u1',
            changedFields: ['password'],
            previous: ['password' => 'old-secret'],
            new: ['password' => 'new-secret'],
        );

        $this->eventService->recordLog($event);

        $doc = EventLog::query()->where('entity_id', 'u1')->first();
        $this->assertNotNull($doc);
        $this->assertSame('[REDACTED]', $doc->previous['password'] ?? null);
        $this->assertSame('[REDACTED]', $doc->new['password'] ?? null);
    }

    private function cleanEventCollections(): void
    {
        try {
            EventStore::query()->delete();
            EventLog::query()->delete();
        } catch (\Throwable) {
            // MongoDB not available in environment
        }
    }
}
