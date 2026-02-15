<?php

namespace Modules\JAV\Tests\Unit\Events;

use Illuminate\Support\Collection;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Dtos\Items;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Events\ItemsFetched;
use Modules\JAV\Events\OnejavJobFailed;
use Modules\JAV\Tests\TestCase;

class JobAndParserEventsTest extends TestCase
{
    protected bool $usesRefreshDatabase = false;

    public function test_onejav_job_failed_event_keeps_failure_payload(): void
    {
        $exception = new \RuntimeException('network timeout');

        $event = new OnejavJobFailed('daily-sync', $exception);

        $this->assertSame('daily-sync', $event->type);
        $this->assertSame($exception, $event->exception);
    }

    public function test_item_parsed_event_exposes_item_and_source_via_contract_methods(): void
    {
        $item = new Item(
            id: 'id-1',
            title: 'Title 1',
            url: 'https://example.com/item-1',
            image: 'https://example.com/item-1.jpg',
            date: now(),
            code: 'CODE-1',
            tags: new Collection(['Drama']),
            size: 2.5,
            description: 'desc',
            actresses: new Collection(['A'])
        );

        $event = new ItemParsed($item, 'onejav');

        $this->assertSame($item, $event->getItem());
        $this->assertSame('onejav', $event->getSource());
    }

    public function test_items_fetched_event_keeps_items_and_paging_state(): void
    {
        $items = new Items(
            items: collect(),
            hasNextPage: true,
            nextPage: 7,
        );

        $event = new ItemsFetched($items, '141jav', 6);

        $this->assertSame($items, $event->items);
        $this->assertSame('141jav', $event->source);
        $this->assertSame(6, $event->currentPage);
    }
}
