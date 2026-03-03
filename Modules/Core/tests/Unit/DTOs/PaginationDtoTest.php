<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\DTOs;

use InvalidArgumentException;
use Modules\Core\DTOs\PaginationDto;
use Modules\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class PaginationDtoTest extends TestCase
{
    #[Test]
    public function test_constructor_with_has_next_page_and_next_page(): void
    {
        $dto = new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: true,
            nextPage: 2,
        );

        $this->assertSame(1, $dto->currentPage);
        $this->assertSame(10, $dto->perPage);
        $this->assertTrue($dto->hasNextPage);
        $this->assertSame(2, $dto->nextPage);
        $this->assertNull($dto->totalPages);
        $this->assertNull($dto->totalItems);
    }

    #[Test]
    public function test_constructor_with_no_next_page(): void
    {
        $dto = new PaginationDto(
            currentPage: 2,
            perPage: 10,
            hasNextPage: false,
            nextPage: null,
        );

        $this->assertSame(2, $dto->currentPage);
        $this->assertFalse($dto->hasNextPage);
        $this->assertNull($dto->nextPage);
    }

    #[Test]
    public function test_constructor_with_total_pages_and_total_items(): void
    {
        $dto = new PaginationDto(
            currentPage: 1,
            perPage: 5,
            hasNextPage: true,
            nextPage: 2,
            totalPages: 3,
            totalItems: 15,
        );

        $this->assertSame(3, $dto->totalPages);
        $this->assertSame(15, $dto->totalItems);
    }

    #[Test]
    public function test_next_page_returns_next_page_when_has_next(): void
    {
        $dto = new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: true,
            nextPage: 2,
        );

        $this->assertSame(2, $dto->nextPage(1));
    }

    #[Test]
    public function test_next_page_returns_default_when_no_next_page(): void
    {
        $dto = new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: false,
            nextPage: null,
        );

        $this->assertSame(1, $dto->nextPage(1));
        $this->assertSame(5, $dto->nextPage(5));
    }

    #[Test]
    public function test_constructor_throws_when_current_page_less_than_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('currentPage must be >= 1.');

        new PaginationDto(
            currentPage: 0,
            perPage: 10,
            hasNextPage: false,
            nextPage: null,
        );
    }

    #[Test]
    public function test_constructor_throws_when_per_page_less_than_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('perPage must be >= 1.');

        new PaginationDto(
            currentPage: 1,
            perPage: 0,
            hasNextPage: false,
            nextPage: null,
        );
    }

    #[Test]
    public function test_constructor_throws_when_has_next_page_but_next_page_null(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nextPage must be not null when hasNextPage is true.');

        new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: true,
            nextPage: null,
        );
    }

    #[Test]
    public function test_constructor_throws_when_has_next_page_but_next_page_less_than_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nextPage must be >= 1 when hasNextPage is true.');

        new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: true,
            nextPage: 0,
        );
    }

    #[Test]
    public function test_constructor_throws_when_no_next_page_but_next_page_not_null(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nextPage must be null when hasNextPage is false.');

        new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: false,
            nextPage: 2,
        );
    }

    #[Test]
    public function test_constructor_throws_when_total_pages_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('totalPages must be >= 0.');

        new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: false,
            nextPage: null,
            totalPages: -1,
        );
    }

    #[Test]
    public function test_constructor_throws_when_total_items_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('totalItems must be >= 0.');

        new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: false,
            nextPage: null,
            totalItems: -1,
        );
    }

    #[Test]
    public function test_constructor_throws_when_current_page_exceeds_total_pages(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('currentPage must be <= totalPages when totalPages is provided.');

        new PaginationDto(
            currentPage: 5,
            perPage: 10,
            hasNextPage: false,
            nextPage: null,
            totalPages: 3,
        );
    }

    #[Test]
    public function test_next_page_throws_when_default_less_than_one(): void
    {
        $dto = new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: false,
            nextPage: null,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Default must be >= 1.');

        $dto->nextPage(0);
    }

    #[Test]
    public function test_next_page_with_custom_default_returns_default_when_no_next(): void
    {
        $dto = new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: false,
            nextPage: null,
        );

        $this->assertSame(99, $dto->nextPage(99));
    }

    #[Test]
    public function test_total_pages_zero_allowed(): void
    {
        $dto = new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: false,
            nextPage: null,
            totalPages: 0,
        );

        $this->assertSame(0, $dto->totalPages);
    }

    #[Test]
    public function test_total_items_zero_allowed(): void
    {
        $dto = new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: false,
            nextPage: null,
            totalItems: 0,
        );

        $this->assertSame(0, $dto->totalItems);
    }

    #[Test]
    public function test_current_page_equals_total_pages_allowed(): void
    {
        $dto = new PaginationDto(
            currentPage: 3,
            perPage: 10,
            hasNextPage: false,
            nextPage: null,
            totalPages: 3,
        );

        $this->assertSame(3, $dto->currentPage);
        $this->assertSame(3, $dto->totalPages);
    }

    #[Test]
    public function test_next_page_throws_when_default_negative(): void
    {
        $dto = new PaginationDto(
            currentPage: 1,
            perPage: 10,
            hasNextPage: false,
            nextPage: null,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Default must be >= 1.');

        $dto->nextPage(-1);
    }
}
