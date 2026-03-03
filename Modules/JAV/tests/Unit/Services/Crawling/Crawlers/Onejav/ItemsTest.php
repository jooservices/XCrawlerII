<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Services\Crawling\Crawlers\Onejav;

use Modules\Core\DTOs\ListDto;
use Modules\JAV\Enums\SourceEnum;
use Modules\JAV\Services\Crawling\Crawlers\Onejav\Items;
use Modules\JAV\Tests\TestCase;

final class ItemsTest extends TestCase
{
    public function test_to_list_dto_returns_list_dto_with_items_and_pagination(): void
    {
        $response = $this->getMockResponseWrapper('onejav_new_page1.html');
        $items = new Items($response);
        $list = $items->toListDto();

        $this->assertInstanceOf(ListDto::class, $list);
        $this->assertGreaterThan(0, $list->items->count());
        $this->assertSame(1, $list->pagination->currentPage);
        $this->assertTrue($list->pagination->hasNextPage);
        $this->assertSame(2, $list->pagination->nextPage);
        $this->assertGreaterThanOrEqual(1, $list->pagination->perPage);
    }

    public function test_to_list_dto_each_item_has_source_and_code(): void
    {
        $response = $this->getMockResponseWrapper('onejav_new_page1.html');
        $list = (new Items($response))->toListDto();

        foreach ($list->items as $movie) {
            $this->assertSame(SourceEnum::Onejav, $movie->source);
            $this->assertNotEmpty($movie->code);
        }
    }

    public function test_to_list_dto_pagination_next_page_null_when_no_next(): void
    {
        $html = $this->loadFixture('onejav_new_page1.html');
        $html = preg_replace('/<a class="pagination-next[^"]*"[^>]*>.*?<\/a>/s', '', $html);
        $response = $this->getMockResponseWrapper('', 200, $html);
        $list = (new Items($response))->toListDto();

        $this->assertFalse($list->pagination->hasNextPage);
        $this->assertNull($list->pagination->nextPage);
    }

    public function test_to_list_dto_empty_list_uses_per_page_at_least_one(): void
    {
        $emptyHtml = '<!DOCTYPE html><html><body><div class="container"></body></html>';
        $response = $this->getMockResponseWrapper('', 200, $emptyHtml);
        $list = (new Items($response))->toListDto();

        $this->assertSame(0, $list->items->count());
        $this->assertSame(1, $list->pagination->currentPage);
        $this->assertSame(1, $list->pagination->perPage);
        $this->assertFalse($list->pagination->hasNextPage);
        $this->assertNull($list->pagination->nextPage);
    }

    public function test_to_list_dto_current_page_parsed_from_pagination(): void
    {
        $response = $this->getMockResponseWrapper('onejav_new_page1.html');
        $list = (new Items($response))->toListDto();

        $this->assertSame(1, $list->pagination->currentPage);
    }

    public function test_to_list_dto_last_page_has_has_next_false_and_next_page_null(): void
    {
        $html = $this->loadFixture('onejav_new_page1.html');
        $html = preg_replace('/<a class="pagination-next[^"]*"[^>]*>.*?<\/a>/s', '', $html);
        $response = $this->getMockResponseWrapper('', 200, $html);
        $list = (new Items($response))->toListDto();

        $this->assertFalse($list->pagination->hasNextPage, 'Last page must have hasNextPage false');
        $this->assertNull($list->pagination->nextPage, 'Last page must have nextPage null');
    }

    public function test_to_list_dto_page_2_reports_current_page_2_and_next_page_3(): void
    {
        $response = $this->getMockResponseWrapper('onejav_2026-03-03_page2.html');
        $list = (new Items($response))->toListDto();

        $this->assertSame(2, $list->pagination->currentPage);
        $this->assertTrue($list->pagination->hasNextPage);
        $this->assertSame(3, $list->pagination->nextPage);
    }
}
