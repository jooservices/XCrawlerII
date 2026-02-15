<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Modules\JAV\Jobs\RefreshAnalyticsSnapshotsJob;
use Modules\JAV\Services\AnalyticsSnapshotService;
use Modules\JAV\Tests\TestCase;

class RefreshAnalyticsSnapshotsJobTest extends TestCase
{
    public function test_handle_refreshes_all_supported_day_windows(): void
    {
        $calls = [];
        $service = new class($calls) extends AnalyticsSnapshotService
        {
            /** @var array<int, array{days:int,force:bool,fallback:bool}> */
            public array $calls;

            /** @param  array<int, array{days:int,force:bool,fallback:bool}>  $calls */
            public function __construct(array $calls = [])
            {
                $this->calls = $calls;
            }

            public function getSnapshot(int $days, bool $forceRefresh = false, bool $allowMySqlFallback = true): array
            {
                $this->calls[] = [
                    'days' => $days,
                    'force' => $forceRefresh,
                    'fallback' => $allowMySqlFallback,
                ];

                return ['totals' => ['jav' => 0, 'actors' => 0, 'tags' => 0]];
            }
        };

        $job = new RefreshAnalyticsSnapshotsJob;
        $job->handle($service);

        $this->assertSame([
            ['days' => 7, 'force' => true, 'fallback' => false],
            ['days' => 14, 'force' => true, 'fallback' => false],
            ['days' => 30, 'force' => true, 'fallback' => false],
            ['days' => 90, 'force' => true, 'fallback' => false],
        ], $service->calls);
    }
}
