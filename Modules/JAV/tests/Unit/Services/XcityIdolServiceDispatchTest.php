<?php

namespace Modules\JAV\Tests\Unit\Services;

use Modules\Core\Facades\Config;
use Modules\JAV\Services\ActorProfileUpsertService;
use Modules\JAV\Services\Clients\XcityClient;
use Modules\JAV\Services\CrawlerResponseCacheService;
use Modules\JAV\Services\XcityIdolService;
use Modules\JAV\Tests\TestCase;

class XcityIdolServiceDispatchTest extends TestCase
{
    public function test_pick_seeds_for_dispatch_returns_empty_for_empty_seed_list(): void
    {
        $service = new XcityIdolService(
            \Mockery::mock(XcityClient::class),
            app(CrawlerResponseCacheService::class),
            new ActorProfileUpsertService
        );

        $selected = $service->pickSeedsForDispatch([], 3);

        $this->assertTrue($selected->isEmpty());
    }

    public function test_pick_seeds_for_dispatch_skips_running_seeds_and_respects_concurrency(): void
    {
        $service = new XcityIdolService(
            \Mockery::mock(XcityClient::class),
            app(CrawlerResponseCacheService::class),
            new ActorProfileUpsertService
        );

        $seedUrls = [
            'seed-a' => 'https://xxx.xcity.jp/idol/?kana=a',
            'seed-i' => 'https://xxx.xcity.jp/idol/?kana=i',
            'seed-u' => 'https://xxx.xcity.jp/idol/?kana=u',
        ];

        Config::set('xcity', 'cursor', '0');
        Config::set('xcity', 'kana_seed-a_running', '1');
        Config::set('xcity', 'kana_seed-i_running', '0');
        Config::set('xcity', 'kana_seed-u_running', '0');

        $selected = $service->pickSeedsForDispatch($seedUrls, 2);

        $this->assertCount(2, $selected);
        $this->assertSame('seed-i', $selected[0]['seed_key']);
        $this->assertSame('seed-u', $selected[1]['seed_key']);
        $this->assertSame('2', Config::get('xcity', 'cursor'));
    }

    public function test_pick_seeds_for_dispatch_wraps_cursor_when_reaching_end(): void
    {
        $service = new XcityIdolService(
            \Mockery::mock(XcityClient::class),
            app(CrawlerResponseCacheService::class),
            new ActorProfileUpsertService
        );

        $seedUrls = [
            'seed-a' => 'https://xxx.xcity.jp/idol/?kana=a',
            'seed-i' => 'https://xxx.xcity.jp/idol/?kana=i',
            'seed-u' => 'https://xxx.xcity.jp/idol/?kana=u',
        ];

        Config::set('xcity', 'cursor', '2');
        Config::set('xcity', 'kana_seed-a_running', '0');
        Config::set('xcity', 'kana_seed-i_running', '0');
        Config::set('xcity', 'kana_seed-u_running', '0');

        $selected = $service->pickSeedsForDispatch($seedUrls, 2);

        $this->assertCount(2, $selected);
        $this->assertSame('seed-u', $selected[0]['seed_key']);
        $this->assertSame('seed-a', $selected[1]['seed_key']);
        $this->assertSame('1', Config::get('xcity', 'cursor'));
    }
}
