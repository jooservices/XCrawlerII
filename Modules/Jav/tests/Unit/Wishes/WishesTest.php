<?php

namespace Modules\Jav\Tests\Unit\Wishes;

use Modules\Jav\Client\Onejav\CrawlingService;
use Modules\Jav\tests\TestCase;

class WishesTest extends TestCase
{
    final public function testWishNew(): void
    {
        $this->wish
            ->wishNew()
            ->wish();

        $crawling = app(CrawlingService::class);
        $items = $crawling->getItems();

        $this->assertCount(10, $items->getItems());
    }

    final public function testWishPopular(): void
    {
        $this->wish
            ->wishPopular()
            ->wish();

        $crawling = app(CrawlingService::class);
        $items = $crawling->getItems('popular');

        $this->assertCount(10, $items->getItems());
    }
}
