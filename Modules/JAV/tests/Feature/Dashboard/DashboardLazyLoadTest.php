<?php

namespace Modules\JAV\Tests\Feature\Dashboard;

use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\TestCase;

class DashboardLazyLoadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['scout.driver' => 'collection']);
    }

    public function test_dashboard_lazy_load_uses_relative_next_page_url_and_returns_more_items(): void
    {
        Jav::factory()->count(31)->create();

        $firstPageAjax = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('jav.blade.dashboard'));

        $firstPageAjax
            ->assertOk()
            ->assertJsonStructure(['html', 'next_page_url']);

        $nextPageUrl = $firstPageAjax->json('next_page_url');

        $this->assertNotNull($nextPageUrl);
        $this->assertStringStartsWith('/', $nextPageUrl);
        $this->assertStringContainsString('page=2', $nextPageUrl);

        $secondPageAjax = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/jav/blade/dashboard?page=2');

        $secondPageAjax
            ->assertOk()
            ->assertJsonStructure(['html', 'next_page_url'])
            ->assertJsonPath('next_page_url', null);

        $this->assertStringContainsString('movie-card', $secondPageAjax->json('html'));
    }
}
