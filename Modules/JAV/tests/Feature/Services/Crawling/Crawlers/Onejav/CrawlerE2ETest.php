<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Feature\Services\Crawling\Crawlers\Onejav;

use Modules\Core\DTOs\ListDto;
use Modules\Core\DTOs\PaginationDto;
use Modules\JAV\DTOs\MovieDto;
use Modules\JAV\Enums\SourceEnum;
use Modules\JAV\Services\Crawling\Client\OnejavClient;
use Modules\JAV\Services\Crawling\Crawlers\Onejav\Crawler;
use Modules\JAV\Tests\TestCase;
use PHPUnit\Framework\Attributes\Timeout;

/**
 * E2E tests against live onejav.com (real HTTP, real HTML).
 * Asserts valid ListDto/MovieDto structure and that DTOs have valid data; no exact value comparison.
 * Run with: php artisan test -c phpunit.integration.xml --group=integration
 * Requires network; may timeout or fail if the site is slow or unavailable.
 *
 * @group integration
 */
#[Timeout(45)]
final class CrawlerE2ETest extends TestCase
{
    private function crawler(): Crawler
    {
        return new Crawler(app(OnejavClient::class));
    }

    public function test_new_returns_valid_list_dto_from_live_site(): void
    {
        $crawler = $this->crawler();
        $list = $crawler->new(1);

        $this->assertInstanceOf(ListDto::class, $list);
        $this->assertInstanceOf(PaginationDto::class, $list->pagination);
        $this->assertGreaterThan(0, $list->items->count(), 'Live /new should return at least one item');
        $this->assertGreaterThanOrEqual(1, $list->pagination->currentPage);
        $this->assertGreaterThanOrEqual(1, $list->pagination->perPage);

        foreach ($list->items as $item) {
            $this->assertValidMovieDto($item);
        }
    }

    public function test_new_first_movie_has_valid_attributes(): void
    {
        $crawler = $this->crawler();
        $list = $crawler->new(1);
        $this->assertGreaterThan(0, $list->items->count(), 'Live /new should return at least one item');

        $movie = $list->items->first();
        $this->assertInstanceOf(MovieDto::class, $movie);
        $this->assertSame(SourceEnum::Onejav, $movie->source);
        $this->assertNotEmpty($movie->code, 'First movie must have non-empty code');
        $this->assertIsString($movie->code);
        $this->assertTrue(
            ($movie->title !== null && $movie->title !== '') || ($movie->cover !== null && $movie->cover !== ''),
            'First movie must have at least title or cover'
        );
    }

    public function test_daily_returns_valid_list_dto_from_live_site(): void
    {
        $crawler = $this->crawler();
        $date = new \DateTimeImmutable('today');
        $list = $crawler->daily($date, 1);

        $this->assertInstanceOf(ListDto::class, $list);
        $this->assertInstanceOf(PaginationDto::class, $list->pagination);
        $this->assertGreaterThanOrEqual(0, $list->items->count());

        foreach ($list->items as $item) {
            $this->assertValidMovieDto($item);
        }
    }

    /**
     * Request daily 2026/03/01 pages 1 to 6; last page (6) must have hasNextPage false and nextPage null.
     * Uses https://onejav.com/2026/03/01?page=N (N=1..6).
     */
    #[Timeout(120)]
    public function test_daily_pagination_from_page_1_to_6_last_page_has_has_next_false_and_next_page_null(): void
    {
        $crawler = $this->crawler();
        $date = new \DateTimeImmutable('2026-03-01');
        $list = null;

        for ($page = 1; $page <= 6; $page++) {
            $list = $crawler->daily($date, $page);
            $this->assertInstanceOf(ListDto::class, $list);
            $this->assertInstanceOf(PaginationDto::class, $list->pagination);
            $this->assertSame($page, $list->pagination->currentPage, "Response for page {$page} must report currentPage {$page}");
        }

        $this->assertSame(6, $list->pagination->currentPage, 'Last page must be 6');
        $this->assertFalse($list->pagination->hasNextPage, 'Last page must have hasNextPage false');
        $this->assertNull($list->pagination->nextPage, 'Last page must have nextPage null');
    }

    private function assertValidMovieDto(MovieDto $movie): void
    {
        $this->assertSame(SourceEnum::Onejav, $movie->source);
        $this->assertNotEmpty($movie->code, 'MovieDto from list should have non-empty code');
        $this->assertIsString($movie->code);
    }
}
