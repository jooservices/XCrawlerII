<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Tests\TestCase;

class DashboardPresetBehaviorTest extends TestCase
{
    public function test_weekly_downloads_preset_limits_results_to_recent_items_and_sorts_by_downloads_desc(): void
    {
        config(['scout.driver' => 'collection']);

        $user = User::factory()->create();

        $recentHigh = Jav::factory()->create([
            'downloads' => 100,
            'created_at' => now()->subDays(2),
        ]);

        $recentLow = Jav::factory()->create([
            'downloads' => 10,
            'created_at' => now()->subDays(3),
        ]);

        $oldVeryHigh = Jav::factory()->create([
            'downloads' => 999,
            'created_at' => now()->subDays(15),
        ]);

        $this->actingAs($user)
            ->get(route('jav.vue.dashboard', [
                'preset' => 'weekly_downloads',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Dashboard/Index', false)
                ->where('preset', 'weekly_downloads')
                ->where('items.data.0.uuid', $recentHigh->uuid)
                ->where('items.data.1.uuid', $recentLow->uuid)
                ->has('items.data', 2)
            );

        $this->assertNotSame($oldVeryHigh->uuid, $recentHigh->uuid);
    }

    public function test_preferred_tags_preset_returns_only_items_matching_users_preferred_tags(): void
    {
        config(['scout.driver' => 'collection']);

        $user = User::factory()->create();
        $preferredTag = Tag::factory()->create(['name' => 'Preferred']);
        $otherTag = Tag::factory()->create(['name' => 'Other']);

        Favorite::query()->create([
            'user_id' => $user->id,
            'favoritable_type' => Tag::class,
            'favoritable_id' => $preferredTag->id,
        ]);

        $matching = Jav::factory()->create();
        $matching->tags()->attach($preferredTag->id);

        Jav::factory()->create()->tags()->attach($otherTag->id);

        $this->actingAs($user)
            ->get(route('jav.vue.dashboard', [
                'preset' => 'preferred_tags',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Dashboard/Index', false)
                ->where('preset', 'preferred_tags')
                ->where('items.data.0.uuid', $matching->uuid)
                ->has('items.data', 1)
            );
    }

    public function test_preferred_tags_preset_returns_empty_when_user_has_no_preferred_tags(): void
    {
        config(['scout.driver' => 'collection']);

        $user = User::factory()->create();
        Jav::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('jav.vue.dashboard', [
                'preset' => 'preferred_tags',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Dashboard/Index', false)
                ->where('preset', 'preferred_tags')
                ->has('items.data', 0)
            );
    }

    public function test_dashboard_rejects_unknown_preset_payload(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('jav.vue.dashboard', [
                'preset' => 'preferred_tags;rm -rf /',
            ]))
            ->assertStatus(302)
            ->assertSessionHasErrors(['preset']);
    }
}
