<?php

namespace Modules\JAV\Tests\Feature\Services;

use Illuminate\Support\Facades\Event;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Services\Clients\MissavBrowserClient;
use Modules\JAV\Services\Missav\ItemsAdapter;
use Modules\JAV\Tests\TestCase;

class MissavPlaywrightNewTest extends TestCase
{
    private static ?string $cachedHtml = null;

    public function test_playwright_can_fetch_and_save_html(): void
    {
        if (getenv('LIVE_MISSAV_TESTS') !== '1') {
            $this->markTestSkipped('Set LIVE_MISSAV_TESTS=1 to run live MissAV Playwright tests.');
        }

        Event::fake([ItemParsed::class]);

        $this->configurePlaywright();
        $html = $this->fetchHtmlOnce();

        $this->assertIsString($html);
        $this->assertNotSame('', trim($html));
        $this->assertStringNotContainsString('Just a moment', $html, 'Cloudflare challenge detected.');
        $this->assertStringNotContainsString('__cf_chl', $html, 'Cloudflare challenge detected.');

        $dir = storage_path('app/tmp/missav-tests');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = $dir.'/missav_new_live.html';
        file_put_contents($path, $html);

        $this->assertFileExists($path);
        $this->assertGreaterThan(1000, filesize($path));

        @unlink($path);
    }

    public function test_playwright_can_parse_new_items(): void
    {
        if (getenv('LIVE_MISSAV_TESTS') !== '1') {
            $this->markTestSkipped('Set LIVE_MISSAV_TESTS=1 to run live MissAV Playwright tests.');
        }

        Event::fake([ItemParsed::class]);

        $this->configurePlaywright();
        $html = $this->fetchHtmlOnce();

        $adapter = new ItemsAdapter($html);
        $items = $adapter->items();

        $this->assertGreaterThan(0, $items->items->count());

        $summary = [
            'count' => $items->items->count(),
            'items' => [],
        ];

        foreach ($items->items as $item) {
            $this->assertNotNull($item->url);
            $this->assertNotNull($item->title);
            $this->assertNotNull($item->id);

            if (count($summary['items']) < 20) {
                $summary['items'][] = [
                    'id' => $item->id,
                    'title' => $item->title,
                    'url' => $item->url,
                    'image' => $item->image,
                ];
            }
        }

        $dir = storage_path('app/tmp/missav-tests');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $summaryPath = $dir.'/missav_new_parse_summary.json';
        file_put_contents($summaryPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function test_playwright_can_read_pagination(): void
    {
        if (getenv('LIVE_MISSAV_TESTS') !== '1') {
            $this->markTestSkipped('Set LIVE_MISSAV_TESTS=1 to run live MissAV Playwright tests.');
        }

        Event::fake([ItemParsed::class]);
        $this->configurePlaywright();

        $html = $this->fetchHtmlOnce();
        $adapter = new ItemsAdapter($html);

        $currentPage = $adapter->currentPage();
        $nextPage = $adapter->nextPage();
        $hasNext = $adapter->hasNextPage();
        $lastPage = $this->extractLastPage($html);

        $this->assertGreaterThanOrEqual(1, $currentPage);
        $this->assertGreaterThanOrEqual(1, $nextPage);

        $summary = [
            'current_page' => $currentPage,
            'next_page' => $nextPage,
            'has_next' => $hasNext,
            'last_page' => $lastPage,
        ];

        $dir = storage_path('app/tmp/missav-tests');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $summaryPath = $dir.'/missav_new_pagination_summary.json';
        file_put_contents($summaryPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function configurePlaywright(): void
    {
        config([
            'jav.missav.playwright.headless' => false,
            'jav.missav.playwright.timeout_ms' => 120000,
            'jav.missav.playwright.wait_until' => 'domcontentloaded',
        ]);
    }

    private function fetchHtmlOnce(): string
    {
        if (self::$cachedHtml !== null) {
            return self::$cachedHtml;
        }

        $client = new MissavBrowserClient('https://missav.ai');
        self::$cachedHtml = $client->fetchHtml('/dm590/en/release');

        return self::$cachedHtml;
    }

    private function extractLastPage(string $html): ?int
    {
        if ($html === '') {
            return null;
        }

        $matches = [];
        preg_match_all('/[?&]page=(\d+)/i', $html, $matches);

        if (! isset($matches[1]) || $matches[1] === []) {
            return null;
        }

        $pages = array_map('intval', $matches[1]);
        $pages = array_filter($pages, static fn (int $page) => $page > 0);

        if ($pages === []) {
            return null;
        }

        return max($pages);
    }
}