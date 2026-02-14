<?php

namespace Modules\JAV\Tests\Unit\Services\Xcity;

use Modules\JAV\Services\Xcity\XcityListAdapter;
use Modules\JAV\Tests\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class XcityListAdapterTest extends TestCase
{
    public function test_it_parses_idols_and_next_page_url(): void
    {
        $crawler = new Crawler($this->loadFixture('xcity_idol_list_page_1.html'));
        $adapter = new XcityListAdapter($crawler);

        $page = $adapter->page();

        $this->assertCount(2, $page->idols);
        $this->assertSame('1001', $page->idols->first()->xcityId);
        $this->assertSame('Airi Kijima', $page->idols->first()->name);
        $this->assertSame('https://xxx.xcity.jp/images/1001.jpg', $page->idols->first()->coverImage);
        $this->assertSame(
            'https://xxx.xcity.jp/idol/?ini=%E3%81%82&kana=%E3%81%82&page=2',
            $page->nextUrl
        );
    }

    public function test_it_returns_null_next_url_on_last_page(): void
    {
        $crawler = new Crawler($this->loadFixture('xcity_idol_list_page_last.html'));
        $adapter = new XcityListAdapter($crawler);

        $page = $adapter->page();

        $this->assertCount(1, $page->idols);
        $this->assertNull($page->nextUrl);
    }
}
