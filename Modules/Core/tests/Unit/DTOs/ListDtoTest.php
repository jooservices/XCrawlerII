<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\DTOs;

use Illuminate\Support\Collection;
use Modules\Core\DTOs\ListDto;
use Modules\Core\DTOs\PaginationDto;
use Modules\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ListDtoTest extends TestCase
{
    private function validPagination(): PaginationDto
    {
        return new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: false,
            nextPage: null,
        );
    }

    #[Test]
    public function test_constructor_sets_items_and_pagination(): void
    {
        $items = new Collection(['a', 'b', 'c']);
        $pagination = $this->validPagination();

        $dto = new ListDto($items, $pagination);

        $this->assertSame($items, $dto->items);
        $this->assertSame($pagination, $dto->pagination);
        $this->assertSame(['a', 'b', 'c'], $dto->items->all());
    }

    #[Test]
    public function test_constructor_with_empty_collection(): void
    {
        $items = new Collection([]);
        $pagination = $this->validPagination();

        $dto = new ListDto($items, $pagination);

        $this->assertSame(0, $dto->items->count());
        $this->assertSame($pagination, $dto->pagination);
    }

    #[Test]
    public function test_empty_collection_and_pagination_with_next_page(): void
    {
        $items = new Collection([]);
        $pagination = new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: true,
            nextPage: 2,
        );

        $dto = new ListDto($items, $pagination);

        $this->assertTrue($dto->items->isEmpty());
        $this->assertSame(2, $dto->pagination->nextPage(1));
    }

    #[Test]
    public function test_large_collection_stored_correctly(): void
    {
        $items = new Collection(range(1, 100));
        $pagination = $this->validPagination();

        $dto = new ListDto($items, $pagination);

        $this->assertSame(100, $dto->items->count());
        $this->assertSame(1, $dto->items->first());
        $this->assertSame(100, $dto->items->last());
    }
}
