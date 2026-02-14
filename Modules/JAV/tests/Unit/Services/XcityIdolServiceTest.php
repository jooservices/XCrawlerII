<?php

namespace Modules\JAV\Tests\Unit\Services;

use Modules\JAV\Models\Actor;
use Modules\JAV\Services\Clients\XcityClient;
use Modules\JAV\Services\XcityIdolService;
use Modules\JAV\Tests\TestCase;

class XcityIdolServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Actor::disableSearchSyncing();
    }

    public function test_sync_kana_page_links_existing_actor_and_creates_new_one(): void
    {
        Actor::create(['name' => 'Airi Kijima']);

        $client = \Mockery::mock(XcityClient::class);
        $client->shouldReceive('get')
            ->times(3)
            ->andReturnUsing(function (string $url) {
                if ($url === 'https://xxx.xcity.jp/idol/?ini=%E3%81%82&kana=%E3%81%82') {
                    return $this->getMockResponse('xcity_idol_list_page_1.html');
                }

                if ($url === 'https://xxx.xcity.jp/idol/detail/1001/' || $url === 'https://xxx.xcity.jp/idol/detail/2002/') {
                    return $this->getMockResponse('xcity_idol_detail_5750.html');
                }

                throw new \RuntimeException("Unexpected URL {$url}");
            });
        $this->app->instance(XcityClient::class, $client);

        $service = $this->app->make(XcityIdolService::class);
        $count = $service->syncKanaPage('kana-a', 'https://xxx.xcity.jp/idol/?ini=%E3%81%82&kana=%E3%81%82');

        $this->assertSame(2, $count);
        $this->assertDatabaseHas('actors', [
            'name' => 'Airi Kijima',
            'xcity_id' => '1001',
            'xcity_cover' => 'https://faws.xcity.jp/actress/large/image/person/thumb_1623904183.jpg',
            'xcity_birth_date' => '1992-09-25',
            'xcity_blood_type' => 'O',
            'xcity_city_of_birth' => 'Osaka',
            'xcity_height' => '155cm',
            'xcity_size' => 'B83(C) W58 H88',
            'xcity_hobby' => 'Reading / Listening to music',
            'xcity_special_skill' => 'Getting lost',
        ]);
        $this->assertDatabaseHas('actors', [
            'name' => 'Mio Tanaka',
            'xcity_id' => '2002',
            'xcity_cover' => 'https://faws.xcity.jp/actress/large/image/person/thumb_1623904183.jpg',
        ]);
        $this->assertDatabaseHas('actor_profile_sources', [
            'source' => 'xcity',
            'source_actor_id' => '1001',
            'is_primary' => 1,
        ]);
        $this->assertDatabaseHas('actor_profile_attributes', [
            'source' => 'xcity',
            'kind' => 'birth_date',
            'value_string' => '1992-09-25',
            'is_primary' => 1,
        ]);
        $this->assertDatabaseHas('actor_profile_attributes', [
            'source' => 'xcity',
            'kind' => 'raw.name',
            'value_label' => 'Name',
            'value_string' => 'Iori Kogawa',
        ]);
    }

    public function test_seed_kana_urls_expands_ini_sub_groups(): void
    {
        $client = \Mockery::mock(XcityClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/idol/')
            ->andReturn($this->getMockResponse('xcity_root_with_kana.html'));
        $client->shouldReceive('get')
            ->withArgs(function (string $url): bool {
                return str_contains($url, 'https://xxx.xcity.jp/idol/?kana=');
            })
            ->andReturnUsing(function (string $url) {
                if (str_contains($url, 'kana=%E3%81%8B')) {
                    return $this->getMockResponse('xcity_kana_ka_with_ini.html');
                }

                if (str_contains($url, 'kana=%E3%81%95')) {
                    return $this->getMockResponse('xcity_kana_sa_without_ini.html');
                }

                return $this->getMockResponse('xcity_kana_sa_without_ini.html');
            });
        $this->app->instance(XcityClient::class, $client);

        $service = $this->app->make(XcityIdolService::class);
        $seeds = array_values($service->seedKanaUrls());

        $this->assertContains('https://xxx.xcity.jp/idol/?kana=%E3%81%8B&ini=%E3%81%8B', $seeds);
        $this->assertContains('https://xxx.xcity.jp/idol/?kana=%E3%81%8B&ini=%E3%81%8D', $seeds);
        $this->assertContains('https://xxx.xcity.jp/idol/?kana=%E3%81%8B&ini=%E3%81%8F', $seeds);
        $this->assertContains('https://xxx.xcity.jp/idol/?kana=%E3%81%8B&ini=%E3%81%91', $seeds);
        $this->assertContains('https://xxx.xcity.jp/idol/?kana=%E3%81%8B&ini=%E3%81%93', $seeds);
        $this->assertContains('https://xxx.xcity.jp/idol/?kana=%E3%81%95&ini=%E3%81%95', $seeds);
    }
}
