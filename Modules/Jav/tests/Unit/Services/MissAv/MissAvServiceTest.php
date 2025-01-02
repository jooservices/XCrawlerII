<?php

namespace Modules\Jav\Tests\Unit\Services\MissAv;

use Illuminate\Support\Facades\Bus;
use Modules\Jav\Jobs\MissAv\FetchItemDetailJob;
use Modules\Jav\Jobs\MissAv\FetchItemsRecentUpdateJob;
use Modules\Jav\Services\MissAv\MissAvService;
use Modules\Jav\tests\TestCase;
use Modules\Jav\Zeus\Wishes\MissAvWish;

class MissAvServiceTest extends TestCase
{
    final public function testRecentUpdate(): void
    {
        Bus::fake([
            FetchItemDetailJob::class,
            FetchItemsRecentUpdateJob::class,
        ]);

        $this->wish = app(MissAvWish::class);
        $this->wish->wishRecentUpdate()->wish();

        app(MissAvService::class)->recentUpdate();

        Bus::assertDispatchedTimes(FetchItemDetailJob::class, 12);
        Bus::assertDispatchedTimes(FetchItemsRecentUpdateJob::class, 1999);
    }
}
