<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Repositories;

use Carbon\CarbonImmutable;
use Modules\Core\Models\MongoDb\EventLog;
use Modules\Core\Repositories\EventLogRepository;
use Modules\Core\Tests\TestCase;

final class EventLogRepositoryTest extends TestCase
{
    private EventLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EventLogRepository;
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
            'event_id' => 'log-'.fake()->uuid(),
            'event_name' => 'entity.updated',
            'occurred_at' => $occurred,
            'entity_type' => 'order',
            'entity_id' => fake()->uuid(),
            'changed_fields' => ['status', 'amount'],
            'previous' => ['status' => 'pending', 'amount' => 50],
            'new' => ['status' => 'paid', 'amount' => 50],
            'correlation_id' => fake()->uuid(),
            'actor_type' => 'user',
            'actor_id' => fake()->uuid(),
        ];

        $model = $this->repository->create($attributes);

        $this->assertInstanceOf(EventLog::class, $model);
        $this->assertSame($attributes['event_id'], $model->event_id);
        $this->assertSame($attributes['entity_type'], $model->entity_type);
        $this->assertSame($attributes['entity_id'], $model->entity_id);
        $this->assertSame($attributes['changed_fields'], $model->changed_fields);
        $this->assertNotNull($model->created_at);
        $this->assertNotNull($model->updated_at);

        $this->assertDatabaseHas(EventLog::COLLECTION, [
            'event_id' => $attributes['event_id'],
        ], 'mongodb');
    }

    public function test_save_updates_existing_model(): void
    {
        $model = EventLog::factory()->create(['event_name' => 'original']);
        $model->event_name = 'entity.deleted';

        $this->repository->save($model);

        $found = EventLog::query()->find($model->getKey());
        $this->assertNotNull($found);
        $this->assertSame('entity.deleted', $found->event_name);
    }

    private function cleanCollection(): void
    {
        try {
            EventLog::query()->delete();
        } catch (\Throwable) {
            // ignore
        }
    }
}
