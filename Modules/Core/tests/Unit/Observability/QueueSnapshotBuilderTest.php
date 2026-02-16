<?php

namespace Modules\Core\Tests\Unit\Observability;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Support\Facades\Queue;
use Modules\Core\Observability\QueueSnapshotBuilder;
use Tests\TestCase;

class QueueSnapshotBuilderTest extends TestCase
{
    public function test_it_builds_snapshot_with_queue_depth(): void
    {
        $queue = \Mockery::mock(QueueContract::class);
        $queue->shouldReceive('size')->once()->with('obs-telemetry')->andReturn(7);

        Queue::shouldReceive('connection')->once()->with('redis')->andReturn($queue);

        $builder = app(QueueSnapshotBuilder::class);
        $snapshot = $builder->build([
            'queue' => 'obs-telemetry',
            'connection' => 'redis',
            'site' => 'xcity.jp',
        ], 12);

        $this->assertSame('obs-telemetry', $snapshot['queue']);
        $this->assertSame('redis', $snapshot['connection']);
        $this->assertSame(12, $snapshot['jobs_per_second']);
        $this->assertSame(7, $snapshot['queue_depth']);
    }

    public function test_it_returns_null_depth_when_queue_size_lookup_fails(): void
    {
        Queue::shouldReceive('connection')->once()->with('redis')->andThrow(new \RuntimeException('queue down'));

        $builder = app(QueueSnapshotBuilder::class);
        $snapshot = $builder->build([
            'queue' => 'obs-telemetry',
            'connection' => 'redis',
        ], 3);

        $this->assertSame(3, $snapshot['jobs_per_second']);
        $this->assertNull($snapshot['queue_depth']);
    }
}
