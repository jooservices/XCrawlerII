<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Models;

use Modules\Core\Database\Factories\EventStoreFactory;
use Modules\Core\Models\MongoDb\EventStore;
use Modules\Core\Tests\TestCase;
use MongoDB\BSON\UTCDateTime;

final class EventStoreTest extends TestCase
{
    public function test_happy_factory_builds_expected_document_shape(): void
    {
        $attrs = EventStoreFactory::new()->make()->getAttributes();

        $this->assertArrayHasKey('event_name', $attrs);
        $this->assertArrayHasKey('aggregate_type', $attrs);
        $this->assertArrayHasKey('aggregate_id', $attrs);
        $this->assertArrayHasKey('payload', $attrs);
        $this->assertInstanceOf(UTCDateTime::class, $attrs['occurred_at']);
    }

    public function test_edge_casts_coerce_expected_types_when_hydrated(): void
    {
        $model = new EventStore([
            'occurred_at' => now(),
            'aggregate_version' => '7',
            'payload' => ['a' => 1],
        ]);

        $this->assertIsInt($model->aggregate_version);
        $this->assertIsArray($model->payload);
    }
}
