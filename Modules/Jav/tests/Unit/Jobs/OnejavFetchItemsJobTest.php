<?php

namespace Modules\Jav\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Event;
use Modules\Jav\Events\OnejavHaveNextPageEvent;
use Modules\Jav\Events\OnejavReferenceCreatedEvent;
use Modules\Jav\Jobs\OnejavFetchItemsJob;
use Modules\Jav\tests\TestCase;

class OnejavFetchItemsJobTest extends TestCase
{
    public function testSuccess(): void
    {
        Event::fake([
            OnejavReferenceCreatedEvent::class,
            OnejavHaveNextPageEvent::class,
        ]);

        OnejavFetchItemsJob::dispatch('new');

        // Fetched last page
        $this->assertSetting(
            'onejav',
            'new_last_page',
            4,
        );
        // Fetched current page
        $this->assertSetting(
            'onejav',
            'new_current_page',
            2,
        );

        // Fetched items
        $this->assertDatabaseCount('onejav', 10, 'mongodb');
        Event::assertDispatched(OnejavReferenceCreatedEvent::class);

        OnejavFetchItemsJob::dispatch('new', 2);
        $this->assertSetting(
            'onejav',
            'new_current_page',
            3,
        );

        OnejavFetchItemsJob::dispatch('new', 3);
        $this->assertSetting(
            'onejav',
            'new_current_page',
            4,
        );

        OnejavFetchItemsJob::dispatch('new', 4);
        $this->assertSetting(
            'onejav',
            'new_current_page',
            1,
        );
    }
}
