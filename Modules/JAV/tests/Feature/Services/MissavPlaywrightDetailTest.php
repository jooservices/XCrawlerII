<?php

namespace Modules\JAV\Tests\Feature\Services;

use Illuminate\Support\Facades\Event;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Services\Clients\MissavBrowserClient;
use Modules\JAV\Services\Missav\ItemAdapter;
use Modules\JAV\Services\Missav\ItemsAdapter;
use Modules\JAV\Tests\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class MissavPlaywrightDetailTest extends TestCase
{
    public function test_playwright_can_parse_item_details(): void
    {
        if (getenv('LIVE_MISSAV_TESTS') !== '1') {
            $this->markTestSkipped('Set LIVE_MISSAV_TESTS=1 to run live MissAV Playwright tests.');
        }

        Event::fake([ItemParsed::class]);

        config([
            'jav.missav.playwright.headless' => false,
            'jav.missav.playwright.timeout_ms' => 120000,
            'jav.missav.playwright.wait_until' => 'domcontentloaded',
        ]);

        $client = new MissavBrowserClient('https://missav.ai');
        $listHtml = $client->fetchHtml('/dm590/en/release');

        $listAdapter = new ItemsAdapter($listHtml);
        $listItems = $listAdapter->items();

        $this->assertGreaterThan(0, $listItems->items->count());

        $summary = [
            'count' => $listItems->items->count(),
            'items' => [],
        ];

        foreach ($listItems->items as $item) {
            $this->assertNotNull($item->url);

            $detailHtml = $client->fetchHtml($item->url);
            $detailCrawler = new Crawler($detailHtml);
            $detailItem = (new ItemAdapter($detailCrawler))->getItem();

            $this->assertNotNull($detailItem->id);
            $this->assertNotNull($detailItem->title);
            $this->assertNotNull($detailItem->url);

            $summary['items'][] = [
                'id' => $detailItem->id,
                'title' => $detailItem->title,
                'url' => $detailItem->url,
                'code' => $detailItem->code,
                'date' => $detailItem->date?->format('Y-m-d'),
                'actresses_count' => $detailItem->actresses->count(),
                'tags_count' => $detailItem->tags->count(),
                'description_length' => $detailItem->description !== null ? strlen($detailItem->description) : 0,
                'image' => $detailItem->image,
                'download' => $detailItem->download,
            ];
        }

        $dir = storage_path('app/tmp/missav-tests');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $summaryPath = $dir.'/missav_detail_parse_summary.json';
        file_put_contents($summaryPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function test_playwright_can_parse_specific_item_details(): void
    {
        if (getenv('LIVE_MISSAV_TESTS') !== '1') {
            $this->markTestSkipped('Set LIVE_MISSAV_TESTS=1 to run live MissAV Playwright tests.');
        }

        Event::fake([ItemParsed::class]);

        config([
            'jav.missav.playwright.headless' => false,
            'jav.missav.playwright.timeout_ms' => 120000,
            'jav.missav.playwright.wait_until' => 'domcontentloaded',
        ]);

        $url = getenv('MISSAV_DETAIL_URL') ?: 'https://missav.ai/en/spsb-038';

        $client = new MissavBrowserClient('https://missav.ai');
        $detailHtml = $client->fetchHtml($url);
        $detailCrawler = new Crawler($detailHtml);
        $adapter = new ItemAdapter($detailCrawler);
        $detailItem = $adapter->getItem();
        $meta = $adapter->getDetailMeta();
        $streamPresent = preg_match('/https?:\\/\\/[^"\']+\\.m3u8/i', $detailHtml) === 1;

        $this->assertNotNull($detailItem->id);
        $this->assertNotNull($detailItem->title);
        $this->assertNotNull($detailItem->url);

        $summary = [
            'id' => $detailItem->id,
            'title' => $detailItem->title,
            'url' => $detailItem->url,
            'code' => $detailItem->code,
            'date' => $detailItem->date?->format('Y-m-d'),
            'actresses' => $detailItem->actresses->values()->all(),
            'genres' => $meta['genres'] ?? [],
            'series' => $meta['series'] ?? [],
            'maker' => $meta['maker'] ?? [],
            'director' => $meta['director'] ?? [],
            'studio' => $meta['studio'] ?? [],
            'producer' => $meta['producer'] ?? [],
            'label' => $meta['label'] ?? [],
            'tag' => $meta['tag'] ?? [],
            'tags' => $detailItem->tags->values()->all(),
            'description' => $detailItem->description,
            'image' => $detailItem->image,
            'download' => $detailItem->download,
            'stream_url_present' => $streamPresent,
        ];

        $dir = storage_path('app/tmp/missav-tests');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $summaryPath = $dir.'/missav_detail_specific_summary.json';
        file_put_contents($summaryPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}