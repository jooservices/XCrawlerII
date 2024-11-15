<?php

namespace Modules\Jav\Tests\Unit\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Modules\Jav\Client\Onejav\CrawlingService;
use Modules\Jav\Events\OnejavDailyProcessedEvent;
use Modules\Jav\Jobs\OnejavDailyFetchJob;
use Modules\Jav\tests\TestCase;

class OnejavDailyFetchJobTest extends TestCase
{
    public function testDaily()
    {
        OnejavDailyFetchJob::dispatch(
            Carbon::now()->format(CrawlingService::DEFAULT_DATE_FORMAT)
        );

        $this->assertDatabaseCount('onejav', 16, 'mongodb');
    }
}
