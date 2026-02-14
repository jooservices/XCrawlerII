<?php

namespace Modules\JAV\Tests\Feature\Dashboard;

use Inertia\Testing\AssertableInertia as Assert;
use App\Models\User;
use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\TestCase;

class DashboardLazyLoadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['scout.driver' => 'collection']);
    }

    public function test_dashboard_route_renders_inertia_and_supports_pagination_query(): void
    {
        Jav::factory()->count(31)->create();
        $this->actingAs(User::factory()->create());

        $firstPage = $this->get(route('jav.vue.dashboard'));
        $firstPage
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('Dashboard/Index', false)
                    ->has('items.data')
            );

        $secondPage = $this->get('/jav/dashboard?page=2');
        $secondPage
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('Dashboard/Index', false)
                    ->has('items.data')
            );
    }
}
