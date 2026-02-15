<?php

namespace Modules\JAV\Tests\Unit\Services;

use Modules\Core\Facades\Config;
use Modules\JAV\Services\ActorProfileUpsertService;
use Modules\JAV\Services\Clients\XcityClient;
use Modules\JAV\Services\XcityIdolService;
use Modules\JAV\Tests\TestCase;

class XcityIdolServiceDispatchTest extends TestCase
{
    public function test_pick_seeds_for_dispatch_returns_empty_for_empty_seed_list(): void
    {
        $service = new XcityIdolService(
            \Mockery::mock(XcityClient::class),
            \Mockery::mock(ActorProfileUpsertService::class)
        );

        $selected = $service->pickSeedsForDispatch([], 3);

        $this->assertTrue($selected->isEmpty());
    }

    public function test_pick_seeds_for_dispatch_skips_running_seeds_and_respects_concurrency(): void
    {
        $service = new XcityIdolService(
            \Mockery::mock(XcityClient::class),
            \Mockery::mock(ActorProfileUpsertService::class)
        );

        $seedUrls = [
            'seed-a' => 'https://xxx.xcity.jp/idol/?kana=a',
            'seed-i' => 'https://xxx.xcity.jp/idol/?kana=i',
            'seed-u' => 'https://xxx.xcity.jp/idol/?kana=u',
        ];

        Config::shouldReceive('get')
            ->with('xcity', 'cursor', 0)
            ->once()
            ->andReturn(0);

        Config::shouldReceive('get')
            ->with('xcity', 'kana_seed-a_running', '0')
            ->once()
            ->andReturn('1');

        Config::shouldReceive('get')
            ->with('xcity', 'kana_seed-i_running', '0')
            ->once()
            ->andReturn('0');

        Config::shouldReceive('get')
            ->with('xcity', 'kana_seed-u_running', '0')
            ->once()
            ->andReturn('0');

        Config::shouldReceive('set')
            ->with('xcity', 'cursor', '2')
            ->once();

        $selected = $service->pickSeedsForDispatch($seedUrls, 2);

        $this->assertCount(2, $selected);
        $this->assertSame('seed-i', $selected[0]['seed_key']);
        $this->assertSame('seed-u', $selected[1]['seed_key']);
    }
}
