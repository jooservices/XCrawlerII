<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use App\Models\User;
use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\TestCase;

class DashboardControllerContractTest extends TestCase
{
    public function test_dashboard_items_happy_returns_paginated_items_payload_for_authenticated_user(): void
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

    public function test_dashboard_items_security_requires_authentication(): void
    {
        $this->getJson(route('jav.api.dashboard.items'))->assertUnauthorized();
    }

    public function test_dashboard_items_unhappy_rejects_invalid_sort_value(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('jav.api.dashboard.items', ['sort' => 'drop_table']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }

    public function test_dashboard_items_weird_accepts_swapped_age_range_without_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('jav.api.dashboard.items', [
                'age_min' => 40,
                'age_max' => 20,
            ]))
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page']);
    }

    public function test_dashboard_items_exploit_rejects_invalid_tags_mode_injection_attempt(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('jav.api.dashboard.items', [
                'tags_mode' => 'any OR 1=1',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tags_mode']);
    }
}
