<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use App\Models\User;
use Modules\Core\Models\CuratedItem;
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

    public function test_dashboard_items_include_featured_projection_fields(): void
    {
        $user = User::factory()->create();
        $featuredMovie = Jav::factory()->create();
        $normalMovie = Jav::factory()->create();

        CuratedItem::query()->create([
            'item_type' => 'jav',
            'item_id' => $featuredMovie->id,
            'curation_type' => 'featured',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('jav.api.dashboard.items'))
            ->assertOk();

        $featured = collect($response->json('data'))
            ->first(fn ($item): bool => (int) ($item['id'] ?? 0) === (int) $featuredMovie->id);

        $normal = collect($response->json('data'))
            ->first(fn ($item): bool => (int) ($item['id'] ?? 0) === (int) $normalMovie->id);

        $this->assertNotNull($featured);
        $this->assertNotNull($normal);
        $this->assertTrue((bool) ($featured['is_featured'] ?? false));
        $this->assertNotEmpty($featured['featured_curation_uuid'] ?? null);
        $this->assertFalse((bool) ($normal['is_featured'] ?? true));
        $this->assertNull($normal['featured_curation_uuid'] ?? null);
    }
}
