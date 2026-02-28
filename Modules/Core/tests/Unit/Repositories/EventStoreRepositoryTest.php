<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Repositories;

use Carbon\CarbonImmutable;
use Modules\Core\Models\MongoDb\EventStore;
use Modules\Core\Repositories\EventStoreRepository;
use Modules\Core\Tests\TestCase;

final class EventStoreRepositoryTest extends TestCase
{
    private EventStoreRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EventStoreRepository;
        $this->cleanCollection();
    }

    protected function tearDown(): void
    {
        $this->cleanCollection();
        parent::tearDown();
    }

    public function test_create_persists_document_with_correct_shape(): void
    {
        $occurred = CarbonImmutable::now();
        $attributes = [
            'event_id' => 'evt-'.fake()->uuid(),
            'event_name' => 'test.created',
            'occurred_at' => $occurred,
            'aggregate_type' => 'test_aggregate',
            'aggregate_id' => fake()->uuid(),
            'aggregate_version' => 1,
            'payload' => ['key' => fake()->word()],
            'correlation_id' => fake()->uuid(),
            'causation_id' => null,
            'actor_type' => 'user',
            'actor_id' => fake()->uuid(),
        ];

        $model = $this->repository->create($attributes);

        $this->assertInstanceOf(EventStore::class, $model);
        $this->assertSame($attributes['event_id'], $model->event_id);
        $this->assertSame($attributes['event_name'], $model->event_name);
        $this->assertSame($attributes['aggregate_type'], $model->aggregate_type);
        $this->assertSame($attributes['aggregate_id'], $model->aggregate_id);
        $this->assertNotNull($model->created_at);
        $this->assertNotNull($model->updated_at);

        $this->assertDatabaseHas(EventStore::COLLECTION, [
            'event_id' => $attributes['event_id'],
        ], 'mongodb');
    }

    public function test_save_updates_existing_model(): void
    {
        $model = EventStore::factory()->create(['event_name' => 'original']);
        $model->event_name = 'updated';

        $this->repository->save($model);

        $found = EventStore::query()->find($model->getKey());
        $this->assertNotNull($found);
        $this->assertSame('updated', $found->event_name);
    }

    private function cleanCollection(): void
    {
        try {
            EventStore::query()->delete();
        } catch (\Throwable) {
            // ignore
        }
    }
}
