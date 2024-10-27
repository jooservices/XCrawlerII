<?php

namespace Modules\Jav\tests\Unit\Services;

use Illuminate\Support\Facades\Event;
use Modules\Jav\Events\OnejavHaveNextPageEvent;
use Modules\Jav\Services\OnejavCrawlingService;
use Modules\Jav\tests\TestCase;

class OnejavCrawlingServiceTest extends TestCase
{
    public function testGetItems()
    {
        Event::fake([
            OnejavHaveNextPageEvent::class,
        ]);

        $result = app(OnejavCrawlingService::class)
            ->getItems();

        $this->assertCount(10, $result);
        $this->assertDatabaseHas(
            'settings',
            [
                'group' => 'onejav',
                'key' => 'new_last_page',
                'value' => 4,
            ],
            'mongodb'
        );

        Event::assertDispatched(OnejavHaveNextPageEvent::class, function ($event) {
            return $event->endpoint === 'new'
                && $event->currentPage === 1
                && $event->lastPage === 4;
        });
    }
}
