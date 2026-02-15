<?php

namespace Modules\JAV\Tests\Unit\Models\Mongo;

use Modules\JAV\Models\Mongo\RecommendationSnapshot;
use PHPUnit\Framework\TestCase;

class RecommendationSnapshotTest extends TestCase
{
    public function test_model_uses_expected_connection_and_collection(): void
    {
        $model = new RecommendationSnapshot;

        $this->assertSame('mongodb', $model->getConnectionName());
        $this->assertSame('recommendation_snapshots', $model->getTable());
        $this->assertFalse($model->timestamps);
    }
}
