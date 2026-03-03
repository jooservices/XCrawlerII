<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Services\Crawling\Crawlers\Onejav;

use JOOservices\Client\Contracts\HttpClientInterface;
use Modules\Core\Services\Client\Client;
use Modules\Core\Services\Client\ClientFactory;
use Modules\Core\DTOs\ListDto;
use Modules\JAV\DTOs\MovieDto;
use Modules\JAV\Services\Crawling\Client\OnejavClient;
use Modules\JAV\Services\Crawling\Crawlers\Onejav\Crawler;
use Modules\JAV\Tests\TestCase;
use Mockery;

final class CrawlerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createCrawlerWithMockedResponse(string $path, string $fixture): Crawler
    {
        $response = $this->getMockResponseWrapper($fixture);
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', OnejavClient::BASE_URI . $path, Mockery::on(function (array $options): bool {
                return isset($options['headers']['User-Agent']) && $options['headers']['User-Agent'] !== '';
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);

        return new Crawler($client);
    }

    private function createCrawlerWithBody(string $path, string $html): Crawler
    {
        $response = $this->getMockResponseWrapper('', 200, $html);
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', OnejavClient::BASE_URI . $path, Mockery::on(function (array $options): bool {
                return isset($options['headers']['User-Agent']) && $options['headers']['User-Agent'] !== '';
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);

        return new Crawler($client);
    }

    public function test_new_builds_path_and_returns_list_dto(): void
    {
        $crawler = $this->createCrawlerWithMockedResponse('/new', 'onejav_new_page1.html');
        $list = $crawler->new(1);

        $this->assertInstanceOf(ListDto::class, $list);
        $this->assertGreaterThan(0, $list->items->count());
    }

    public function test_new_with_page_appends_query(): void
    {
        $crawler = $this->createCrawlerWithMockedResponse('/new?page=2', 'onejav_new_page1.html');
        $list = $crawler->new(2);

        $this->assertInstanceOf(ListDto::class, $list);
    }

    public function test_popular_builds_path_and_returns_list_dto(): void
    {
        $crawler = $this->createCrawlerWithMockedResponse('/popular/', 'onejav_popular.html');
        $list = $crawler->popular(1);

        $this->assertInstanceOf(ListDto::class, $list);
    }

    public function test_daily_builds_ymd_path_and_returns_list_dto(): void
    {
        $crawler = $this->createCrawlerWithMockedResponse('/2026/03/03', 'onejav_2026-03-03_page1.html');
        $date = new \DateTimeImmutable('2026-03-03');
        $list = $crawler->daily($date, 1);

        $this->assertInstanceOf(ListDto::class, $list);
    }

    public function test_daily_with_page_appends_query(): void
    {
        $crawler = $this->createCrawlerWithMockedResponse('/2026/03/03?page=2', 'onejav_2026-03-03_page2.html');
        $date = new \DateTimeImmutable('2026-03-03');
        $list = $crawler->daily($date, 2);

        $this->assertInstanceOf(ListDto::class, $list);
    }

    public function test_item_with_path_returns_movie_dto(): void
    {
        $crawler = $this->createCrawlerWithMockedResponse('/torrent/fc2ppv4857395', 'onejav_new_page1.html');
        $movie = $crawler->item('fc2ppv4857395');

        $this->assertInstanceOf(MovieDto::class, $movie);
        $this->assertSame('FC2-PPV-4857395', $movie->code);
    }

    public function test_item_with_full_url_extracts_path(): void
    {
        $crawler = $this->createCrawlerWithMockedResponse('/torrent/fc2ppv4857395', 'onejav_new_page1.html');
        $movie = $crawler->item('https://onejav.com/torrent/fc2ppv4857395');

        $this->assertInstanceOf(MovieDto::class, $movie);
    }

    public function test_new_last_page_has_has_next_false_and_next_page_null(): void
    {
        $html = $this->loadFixture('onejav_new_page1.html');
        $html = preg_replace('/<a class="pagination-next[^"]*"[^>]*>.*?<\/a>/s', '', $html);
        $crawler = $this->createCrawlerWithBody('/new?page=2', $html);
        $list = $crawler->new(2);

        $this->assertFalse($list->pagination->hasNextPage, 'Last page must have hasNextPage false');
        $this->assertNull($list->pagination->nextPage, 'Last page must have nextPage null');
    }
}
