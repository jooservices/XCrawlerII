<?php

namespace Modules\JAV\Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Modules\JAV\Models\Jav;
use Modules\JAV\Services\AnalyticsReadService;
use Modules\JAV\Tests\TestCase;

class AnalyticsReadServiceSyncHealthTest extends TestCase
{
    public function test_snapshot_sync_health_reflects_jobs_and_failed_jobs_tables(): void
    {
        Jav::factory()->create();

        DB::table('jobs')->insert([
            'queue' => 'jav',
            'payload' => '{}',
            'attempts' => 0,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'jav',
            'payload' => '{}',
            'exception' => 'RuntimeException: test',
            'failed_at' => now(),
        ]);

        $service = app(AnalyticsReadService::class);
        $payload = $service->getSnapshot(7);

        $this->assertSame(1, $payload['syncHealth']['pending_jobs']);
        $this->assertSame(1, $payload['syncHealth']['failed_jobs_24h']);
    }
}
