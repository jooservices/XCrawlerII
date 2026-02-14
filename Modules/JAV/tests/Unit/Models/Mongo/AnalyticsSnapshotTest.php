<?php

namespace Modules\JAV\Tests\Unit\Models\Mongo;

use Modules\JAV\Models\Mongo\AnalyticsSnapshot;
use PHPUnit\Framework\TestCase;

class AnalyticsSnapshotTest extends TestCase
{
    public function test_model_uses_expected_connection_and_collection(): void
    {
        $model = new AnalyticsSnapshot();

        $this->assertSame('mongodb', $model->getConnectionName());
        $this->assertSame('analytics_snapshots', $model->getTable());
        $this->assertFalse($model->timestamps);
    }
}
