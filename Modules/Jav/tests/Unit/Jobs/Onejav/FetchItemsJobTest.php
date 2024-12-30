<?php

namespace Modules\Jav\Tests\Unit\Jobs\Onejav;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Modules\Jav\Client\Onejav\CrawlingService;
use Modules\Jav\Events\Onejav\HaveNextPageEvent;
use Modules\Jav\Jobs\Onejav\FetchItemsJob;
use Modules\Jav\Services\Onejav\OnejavService;
use Modules\Jav\tests\TestCase;

class FetchItemsJobTest extends TestCase
{
    final public function testWithDaily(): void
    {
        Event::fake([
            HaveNextPageEvent::class,
        ]);

        $this->wish->wishDaily()->wish();

        $daily = Carbon::now()->format(CrawlingService::DEFAULT_DATE_FORMAT);
        FetchItemsJob::dispatch($daily);

        $this->assertDatabaseCount('onejav', 10, 'mongodb');
        Event::assertDispatched(HaveNextPageEvent::class);

        $this->assertSetting(OnejavService::SETTING_GROUP, $daily . '_current_page', 2);
        $this->assertSetting(OnejavService::SETTING_GROUP, $daily . '_last_page', 2);
    }

    final public function testWithDailyLoop(): void
    {
        Event::fake([
            HaveNextPageEvent::class,
        ]);

        $this->wish->wishDaily()->wish();

        $daily = Carbon::now()->format(CrawlingService::DEFAULT_DATE_FORMAT);
        FetchItemsJob::dispatch($daily, 1, true);

        $this->assertDatabaseCount('onejav', 16, 'mongodb');
        Event::assertDispatched(HaveNextPageEvent::class);

        $this->assertSetting(OnejavService::SETTING_GROUP, $daily . '_current_page', 1);
        $this->assertSetting(OnejavService::SETTING_GROUP, $daily . '_last_page', 2);
    }

    final public function testNewEndOfPages(): void
    {
        Event::fake([
            HaveNextPageEvent::class,
        ]);

        $this->wish->wishNew()->wish();

        FetchItemsJob::dispatch('new', 4);
        $this->assertSetting(
            'onejav',
            'new_current_page',
            1,
        );
    }
}
