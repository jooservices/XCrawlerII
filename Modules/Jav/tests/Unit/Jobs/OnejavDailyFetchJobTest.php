<?php

namespace Modules\Jav\Tests\Unit\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Modules\Jav\Events\OnejavDailyProcessedEvent;
use Modules\Jav\Jobs\OnejavDailyFetchJob;
use Modules\Jav\Onejav\CrawlingService;
use Modules\Jav\tests\TestCase;

class OnejavDailyFetchJobTest extends TestCase
{
    public function testDaily()
    {
        Event::fake([
            OnejavDailyProcessedEvent::class,
        ]);

        OnejavDailyFetchJob::dispatch(
            Carbon::now()->format(CrawlingService::DEFAULT_DATE_FORMAT)
        );

        Event::assertDispatchedTimes(OnejavDailyProcessedEvent::class, 2);
        $this->assertDatabaseCount('onejav', 16, 'mongodb');
    }
}
