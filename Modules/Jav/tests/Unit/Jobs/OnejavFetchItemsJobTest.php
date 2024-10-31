<?php

namespace Modules\Jav\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Event;
use Modules\Jav\Events\OnejavReferenceCreatedEvent;
use Modules\Jav\Jobs\OnejavFetchItemsJob;
use Modules\Jav\tests\TestCase;

class OnejavFetchItemsJobTest extends TestCase
{
    public function testSuccess(): void
    {
        Event::fake([
            OnejavReferenceCreatedEvent::class,
        ]);
        OnejavFetchItemsJob::dispatch('new');

        // Fetched last page
        $this->assertDatabaseHas(
            'settings',
            [
                'group' => 'onejav',
                'key' => 'new_last_page',
                'value' => 4,
            ],
            'mongodb'
        );
        // Fetched current page
        $this->assertDatabaseHas(
            'settings',
            [
                'group' => 'onejav',
                'key' => 'new_current_page',
                'value' => 2,
            ],
            'mongodb'
        );
        // Fetched items
        $this->assertDatabaseCount('onejav', 10, 'mongodb');
        Event::assertDispatched(OnejavReferenceCreatedEvent::class);

        OnejavFetchItemsJob::dispatch(
            'new',
            2
        );
        $this->assertDatabaseHas(
            'settings',
            [
                'group' => 'onejav',
                'key' => 'new_current_page',
                'value' => 3,
            ],
            'mongodb'
        );
    }
}
