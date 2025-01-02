<?php

namespace Modules\Jav\Tests\Unit\Services\MissAv;

use Modules\Jav\Client\MissAv\CrawlingService;
use Modules\Jav\Dto\MissAv\ItemDetailDto;
use Modules\Jav\Dto\MissAv\ItemsDto;
use Modules\Jav\tests\TestCase;

class CrawlingServiceTest extends TestCase
{
    final public function testGetItems(): void
    {
        $service = app(CrawlingService::class);
        $itemsDto = $service->getItems();

        $this->assertInstanceOf(ItemsDto::class, $itemsDto);
        $this->assertEquals(1, $itemsDto->getPage());
        $this->assertEquals(2000, $itemsDto->getLastPage());
        $this->assertEquals(12, $itemsDto->getItems()->count());
    }

    final public function testGetItemDetail(): void
    {
        $url = 'https://missav123.com/dm15/en/achj-038-english-subtitle';
        $service = app(CrawlingService::class);
        $itemDetail = $service->itemDetail($url);

        $this->assertInstanceOf(ItemDetailDto::class, $itemDetail);
    }
}
