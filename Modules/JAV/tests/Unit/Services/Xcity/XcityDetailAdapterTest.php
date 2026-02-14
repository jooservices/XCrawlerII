<?php

namespace Modules\JAV\Tests\Unit\Services\Xcity;

use Modules\JAV\Services\Xcity\XcityDetailAdapter;
use Modules\JAV\Tests\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class XcityDetailAdapterTest extends TestCase
{
    public function test_it_parses_profile_fields_from_detail_page(): void
    {
        $crawler = new Crawler($this->loadFixture('xcity_idol_detail_5750.html'));
        $adapter = new XcityDetailAdapter($crawler);

        $profile = $adapter->profile();

        $this->assertSame('Iori Kogawa', $profile['name']);
        $this->assertSame('https://faws.xcity.jp/actress/large/image/person/thumb_1623904183.jpg', $profile['cover_image']);
        $this->assertSame('1992 Sep 25', $profile['fields']['birth_date'] ?? null);
        $this->assertSame('O Type', $profile['fields']['blood_type'] ?? null);
        $this->assertSame('Osaka', $profile['fields']['city_of_birth'] ?? null);
        $this->assertSame('155cm', $profile['fields']['height'] ?? null);
        $this->assertSame('B83(C) W58 H88', $profile['fields']['size'] ?? null);
        $this->assertSame('Reading / Listening to music', $profile['fields']['hobby'] ?? null);
        $this->assertSame('Getting lost', $profile['fields']['special_skill'] ?? null);
        $this->assertSame('O Type', $profile['raw_fields']['Blood Type'] ?? null);
        $this->assertSame('1992 Sep 25', $profile['raw_fields']['Date of birth'] ?? null);
    }
}
