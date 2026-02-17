<?php

namespace Modules\JAV\Tests\Unit\Services;

use Illuminate\Support\Facades\Event;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Services\Missav\ItemAdapter;
use Modules\JAV\Tests\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class MissavItemAdapterTest extends TestCase
{
    public function test_parses_list_item_from_minimal_html(): void
    {
        Event::fake([ItemParsed::class]);

        $html = <<<HTML
        <div class="thumbnail group">
            <a href="https://missav.ai/en/abp-123">ABP-123 Sample Title</a>
            <div class="my-2 text-sm text-nord4 truncate">
                <a>ABP-123 Sample Title</a>
            </div>
            <img data-src="https://img.test/abp-123.jpg" />
        </div>
        HTML;

        $crawler = new Crawler($html);
        $node = $crawler->filter('div.thumbnail.group')->first();
        $adapter = new ItemAdapter($node);
        $item = $adapter->getItem();

        $this->assertSame('ABP-123', $item->code);
        $this->assertSame('abp-123', $item->id);
        $this->assertSame('https://missav.ai/en/abp-123', $item->url);
        $this->assertSame('ABP-123 Sample Title', $item->title);
        $this->assertSame('https://img.test/abp-123.jpg', $item->image);
    }

    public function test_parses_detail_fixture(): void
    {
        Event::fake([ItemParsed::class]);

        $html = $this->loadFixture('missav/missav_item_spsb-038.html');
        $crawler = new Crawler($html);
        $adapter = new ItemAdapter($crawler);
        $item = $adapter->getItem();
        $meta = $adapter->getDetailMeta();

        $this->assertSame('SPSB-038', $item->code);
        $this->assertSame('https://missav.ai/en/spsb-038', $item->url);
        $this->assertSame('2026-02-16', $item->date?->format('Y-m-d'));
        $this->assertSame(['Flower Hunting'], $item->actresses->values()->all());
        $this->assertSame(['Female Warrior', 'Individual'], $meta['genres']);
        $this->assertSame(['特捜救命部隊セルウォーリア'], $meta['series']);
        $this->assertSame(['GIGA'], $meta['maker']);
        $this->assertSame([], $meta['studio']);
        $this->assertSame([], $meta['producer']);
        $this->assertSame(['壬生乃'], $meta['director']);
        $this->assertSame(['----'], $meta['label']);
        $this->assertSame(['SPSB'], $meta['tag']);

        $this->assertNotNull($item->title);
        $this->assertNotNull($item->image);
    }

    public function test_parses_detail_with_meta_actors_and_stream_url(): void
    {
        Event::fake([ItemParsed::class]);

        $html = <<<HTML
        <html><head>
            <meta property="og:video:release_date" content="2026-01-15" />
            <meta property="og:url" content="https://missav.ai/en/abp-123" />
            <meta property="og:title" content="ABP-123 Example" />
            <meta property="og:image" content="https://img.test/abp-123.jpg" />
            <meta property="og:video:actor" content="Actor A" />
            <meta property="og:video:actor" content="Actor B" />
        </head><body>
            <script>var source = "https://cdn.test/stream.m3u8";</script>
            <a href="https://cdn.test/download.mp4">Download</a>
        </body></html>
        HTML;

        $crawler = new Crawler($html);
        $adapter = new ItemAdapter($crawler);
        $item = $adapter->getItem();

        $this->assertSame('ABP-123', $item->code);
        $this->assertSame('https://missav.ai/en/abp-123', $item->url);
        $this->assertSame('2026-01-15', $item->date?->format('Y-m-d'));
        $this->assertSame(['Actor A', 'Actor B'], $item->actresses->values()->all());
        $this->assertSame('https://cdn.test/stream.m3u8', $item->download);
        $this->assertSame('https://img.test/abp-123.jpg', $item->image);
    }
}
