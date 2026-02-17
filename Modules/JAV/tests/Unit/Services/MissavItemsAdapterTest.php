<?php

namespace Modules\JAV\Tests\Unit\Services;

use Illuminate\Support\Facades\Event;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Services\Missav\ItemsAdapter;
use Modules\JAV\Tests\TestCase;

class MissavItemsAdapterTest extends TestCase
{
    public function test_empty_html_returns_empty_items(): void
    {
        Event::fake([ItemParsed::class]);

        $adapter = new ItemsAdapter('');
        $items = $adapter->items();

        $this->assertSame(0, $items->items->count());
        $this->assertFalse($items->hasNextPage);
        $this->assertSame(1, $items->nextPage);
        $this->assertSame(1, $adapter->currentPage());
        $this->assertFalse($adapter->hasNextPage());
    }

    public function test_parses_items_from_fixture(): void
    {
        Event::fake([ItemParsed::class]);

        $html = $this->loadFixture('missav/missav_new.html');
        $adapter = new ItemsAdapter($html);
        $items = $adapter->items();

        $this->assertGreaterThan(0, $items->items->count());
        $this->assertGreaterThanOrEqual(1, $adapter->currentPage());
        $this->assertGreaterThanOrEqual(1, $adapter->nextPage());

        $ids = [];
        foreach ($items->items as $item) {
            $this->assertNotNull($item->url);
            $this->assertNotNull($item->title);
            $this->assertNotNull($item->id);

            if ($item->id !== null) {
                $ids[] = $item->id;
            }
        }

        $this->assertSame(count($ids), count(array_unique($ids)));

        Event::assertDispatched(ItemParsed::class, $items->items->count());
        Event::assertDispatched(ItemParsed::class, function (ItemParsed $event) {
            return $event->source === 'missav';
        });
    }

    public function test_reads_pagination_from_rel_next_and_input_page(): void
    {
        $html = <<<HTML
        <html><body>
            <input name="page" value="3" />
            <a rel="next" href="/dm590/en/release?page=4"></a>
        </body></html>
        HTML;

        $adapter = new ItemsAdapter($html);

        $this->assertSame(3, $adapter->currentPage());
        $this->assertTrue($adapter->hasNextPage());
        $this->assertSame(4, $adapter->nextPage());
    }

    public function test_next_page_falls_back_to_current_plus_one(): void
    {
        $html = <<<HTML
        <html><body>
            <input name="page" value="2" />
            <a rel="next" href="/dm590/en/release?sort=popular"></a>
        </body></html>
        HTML;

        $adapter = new ItemsAdapter($html);

        $this->assertSame(2, $adapter->currentPage());
        $this->assertSame(3, $adapter->nextPage());
    }
}