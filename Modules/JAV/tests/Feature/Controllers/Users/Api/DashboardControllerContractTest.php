<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use App\Models\User;
use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\TestCase;

class DashboardControllerContractTest extends TestCase
{
    public function test_dashboard_items_endpoint_returns_paginated_items_payload(): void
    {
        Jav::factory()->count(35)->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('jav.api.dashboard.items'))
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'next_page_url',
                'last_page',
            ]);
    }
}
