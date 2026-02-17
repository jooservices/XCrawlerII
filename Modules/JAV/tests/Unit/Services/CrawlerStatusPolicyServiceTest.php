<?php

namespace Modules\JAV\Tests\Unit\Services;

use Modules\Core\Facades\Config;
use Modules\JAV\Services\CrawlerStatusPolicyService;
use Modules\JAV\Tests\TestCase;

class CrawlerStatusPolicyServiceTest extends TestCase
{
    public function test_resolves_default_policy(): void
    {
        $service = app(CrawlerStatusPolicyService::class);
        $policy = $service->resolvePolicy(404, false);

        $this->assertSame('skip', $policy['action']);
        $this->assertTrue($policy['count_as_tail']);
    }

    public function test_resolves_custom_policy_from_config(): void
    {
        Config::set('crawling', 'status_code_action', json_encode([
            '429' => ['action' => 'cooldown', 'delay_sec' => 600, 'count_as_tail' => false],
            'default' => ['action' => 'retry', 'delay_sec' => 15, 'count_as_tail' => false],
        ]));

        $service = app(CrawlerStatusPolicyService::class);
        $policy = $service->resolvePolicy(429, false);

        $this->assertSame('cooldown', $policy['action']);
        $this->assertSame(600, $policy['delay_sec']);
    }
}
