<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Services\Crawling\Crawlers\Onejav;

use Modules\JAV\Enums\SourceEnum;
use Modules\JAV\Services\Crawling\Crawlers\Onejav\Item;
use Modules\JAV\Tests\TestCase;
use Symfony\Component\DomCrawler\Crawler;

final class ItemTest extends TestCase
{
    public function test_to_movie_from_list_row_has_source_code_title_cover(): void
    {
        $html = $this->loadFixture('onejav_new_page1.html');
        $crawler = new Crawler($html);
        $node = $crawler->filter('.card.mb-3 .columns')->first();

        $item = new Item($node, SourceEnum::Onejav);
        $movie = $item->toMovie();

        $this->assertSame(SourceEnum::Onejav, $movie->source);
        $this->assertSame('FC2-PPV-4857395', $movie->code);
        $this->assertNotNull($movie->title);
        $this->assertStringContainsString('FC2', $movie->title ?? '');
        $this->assertNotNull($movie->cover);
        $this->assertStringContainsString('fc2.com', $movie->cover ?? '');
    }

    public function test_to_movie_extracts_release_date(): void
    {
        $html = $this->loadFixture('onejav_new_page1.html');
        $crawler = new Crawler($html);
        $node = $crawler->filter('.card.mb-3 .columns')->first();

        $movie = (new Item($node, SourceEnum::Onejav))->toMovie();

        $this->assertNotNull($movie->releaseDate);
        $this->assertSame('2026-03-03', $movie->releaseDate->format('Y-m-d'));
    }

    public function test_to_movie_extracts_tags(): void
    {
        $html = $this->loadFixture('onejav_new_page1.html');
        $crawler = new Crawler($html);
        $node = $crawler->filter('.card.mb-3 .columns')->first();

        $movie = (new Item($node, SourceEnum::Onejav))->toMovie();

        $this->assertNotEmpty($movie->tags);
        $tagNames = array_map(fn ($t) => $t->name, $movie->tags);
        $this->assertContains('4K', $tagNames);
        $this->assertContains('FC2', $tagNames);
    }

    public function test_to_movie_code_normalized_fc2_ppv(): void
    {
        $html = $this->loadFixture('onejav_new_page1.html');
        $crawler = new Crawler($html);
        $node = $crawler->filter('.card.mb-3 .columns')->first();

        $movie = (new Item($node, SourceEnum::Onejav))->toMovie();

        $this->assertSame('FC2-PPV-4857395', $movie->code);
    }

    public function test_to_movie_optional_fields_null_when_missing(): void
    {
        $minimalHtml = <<<'HTML'
        <div class="columns">
            <div class="column"><img class="image" src="https://example.com/cover.jpg"></div>
            <div class="column is-5">
                <h5 class="title"><a href="/torrent/ABC123">ABC123</a></h5>
                <p class="subtitle"></p>
                <div class="tags"></div>
                <div class="panel"></div>
            </div>
        </div>
        HTML;
        $crawler = new Crawler($minimalHtml);
        $node = $crawler->filter('.columns')->first();

        $movie = (new Item($node, SourceEnum::Onejav))->toMovie();

        $this->assertSame('ABC-123', $movie->code);
        $this->assertNull($movie->title);
        $this->assertSame('https://example.com/cover.jpg', $movie->cover);
        $this->assertEmpty($movie->tags);
        $this->assertEmpty($movie->actors);
    }
}
