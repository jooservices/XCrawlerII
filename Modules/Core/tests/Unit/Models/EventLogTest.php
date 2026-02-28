<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Models;

use Modules\Core\Database\Factories\EventLogFactory;
use Modules\Core\Models\MongoDb\EventLog;
use Modules\Core\Tests\TestCase;
use MongoDB\BSON\UTCDateTime;

final class EventLogTest extends TestCase
{
    public function test_happy_factory_builds_expected_document_shape(): void
    {
        $attrs = EventLogFactory::new()->make()->getAttributes();

        $this->assertArrayHasKey('event_name', $attrs);
        $this->assertArrayHasKey('entity_type', $attrs);
        $this->assertArrayHasKey('entity_id', $attrs);
        $this->assertArrayHasKey('changed_fields', $attrs);
        $this->assertInstanceOf(UTCDateTime::class, $attrs['occurred_at']);
    }

    public function test_edge_casts_arrays_for_changed_fields_previous_and_new(): void
    {
        $model = new EventLog([
            'changed_fields' => ['status'],
            'previous' => ['status' => 'pending'],
            'new' => ['status' => 'paid'],
        ]);

        $this->assertIsArray($model->changed_fields);
        $this->assertIsArray($model->previous);
        $this->assertIsArray($model->new);
    }
}
