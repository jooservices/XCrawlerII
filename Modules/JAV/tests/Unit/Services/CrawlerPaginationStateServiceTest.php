<?php

namespace Modules\JAV\Tests\Unit\Services;

use Modules\Core\Facades\Config;
use Modules\JAV\Services\CrawlerPaginationStateService;
use Modules\JAV\Tests\TestCase;

class CrawlerPaginationStateServiceTest extends TestCase
{
    public function test_record_success_advances_or_resets(): void
    {
        $service = app(CrawlerPaginationStateService::class);

        $state = $service->recordSuccess('onejav', 'new', 5, true, 'new_page');
        $this->assertSame(6, $state['current_page']);
        $this->assertSame(0, $state['current_page_failures']);

        $state = $service->recordSuccess('onejav', 'new', 6, false, 'new_page');
        $this->assertSame(1, $state['current_page']);
    }

    public function test_record_failure_retries_then_advances(): void
    {
        $service = app(CrawlerPaginationStateService::class);

        $result = $service->recordFailure('onejav', 'new', 2, 3, 5, true, 'new_page');
        $this->assertSame('retry_same', $result['action']);
        $this->assertSame(1, $result['state']['current_page_failures']);
        $this->assertSame(2, $result['state']['current_page']);

        $service->recordFailure('onejav', 'new', 2, 3, 5, true, 'new_page');
        $result = $service->recordFailure('onejav', 'new', 2, 3, 5, true, 'new_page');
        $this->assertSame('advance', $result['action']);
        $this->assertSame(3, $result['state']['current_page']);
        $this->assertSame(1, $result['state']['consecutive_skips']);
    }

    public function test_record_failure_resets_after_jump_limit(): void
    {
        $service = app(CrawlerPaginationStateService::class);

        $service->recordFailure('onejav', 'new', 2, 1, 2, true, 'new_page');
        $result = $service->recordFailure('onejav', 'new', 3, 1, 2, true, 'new_page');

        $this->assertSame('reset', $result['action']);
        $this->assertSame(1, $result['state']['current_page']);
        $this->assertSame(0, $result['state']['consecutive_skips']);
    }

    public function test_legacy_key_fallback_is_used(): void
    {
        Config::set('onejav', 'new_page', '9');

        $service = app(CrawlerPaginationStateService::class);
        $state = $service->getState('onejav', 'new', 'new_page');

        $this->assertSame(9, $state['current_page']);
    }
}
