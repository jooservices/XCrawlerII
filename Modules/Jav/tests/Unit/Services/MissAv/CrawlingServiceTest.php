<?php

namespace Modules\Jav\Tests\Unit\Services\MissAv;

use Modules\Jav\Client\MissAv\CrawlingService;
use Modules\Jav\Dto\MissAv\ItemDetailDto;
use Modules\Jav\Dto\MissAv\ItemsDto;
use Modules\Jav\tests\TestCase;
use Modules\Jav\Zeus\Wishes\MissAvWish;

class CrawlingServiceTest extends TestCase
{
    final public function testGetItems(): void
    {
        $this->wish = app(MissAvWish::class);
        $this->wish->wishRecentUpdate()->wish();

        $service = app(CrawlingService::class);
        $itemsDto = $service->getItems();

        $this->assertInstanceOf(ItemsDto::class, $itemsDto);
        $this->assertEquals(1, $itemsDto->getPage());
        $this->assertEquals(2000, $itemsDto->getLastPage());
        $this->assertEquals(12, $itemsDto->getItems()->count());
    }

    final public function testGetItemDetail(): void
    {
        $this->wish = app(MissAvWish::class);
        $this->wish->wishDetail()->wish();

        $service = app(CrawlingService::class);
        $itemDetail = $service->itemDetail($this->faker->url);

        $this->assertInstanceOf(ItemDetailDto::class, $itemDetail);
    }
}
