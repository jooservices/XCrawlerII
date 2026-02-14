<?php

namespace Modules\JAV\Tests\Feature;

use App\Models\User;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Watchlist;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WatchlistControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_watchlist(): void
    {
        $response = $this->actingAs($this->user)->get(route('watchlist.index'));

        $response->assertOk();
        $response->assertViewIs('jav::watchlist.index');
    }

    public function test_guest_cannot_view_watchlist(): void
    {
        $response = $this->get(route('watchlist.index'));

        $response->assertStatus(302);
    }

    public function test_user_can_add_movie_to_watchlist(): void
    {
        $jav = Jav::factory()->create();

        $response = $this->actingAs($this->user)->postJson(route('watchlist.store'), [
            'jav_id' => $jav->id,
            'status' => 'to_watch',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('watchlists', [
            'user_id' => $this->user->id,
            'jav_id' => $jav->id,
            'status' => 'to_watch',
        ]);
    }

    public function test_add_to_watchlist_validates_jav_id(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('watchlist.store'), [
            'jav_id' => 99999, // Non-existent
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['jav_id']);
    }

    public function test_user_can_update_watchlist_status(): void
    {
        $jav = Jav::factory()->create();
        $watchlist = Watchlist::factory()->create([
            'user_id' => $this->user->id,
            'jav_id' => $jav->id,
            'status' => 'to_watch',
        ]);

        $response = $this->actingAs($this->user)->putJson(route('watchlist.update', $watchlist), [
            'status' => 'watched',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('watchlists', [
            'id' => $watchlist->id,
            'status' => 'watched',
        ]);
    }

    public function test_user_cannot_update_another_users_watchlist(): void
    {
        $otherUser = User::factory()->create();
        $jav = Jav::factory()->create();
        $watchlist = Watchlist::factory()->create([
            'user_id' => $otherUser->id,
            'jav_id' => $jav->id,
        ]);

        $response = $this->actingAs($this->user)->putJson(route('watchlist.update', $watchlist), [
            'status' => 'watched',
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_remove_from_watchlist(): void
    {
        $jav = Jav::factory()->create();
        $watchlist = Watchlist::factory()->create([
            'user_id' => $this->user->id,
            'jav_id' => $jav->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(route('watchlist.destroy', $watchlist));

        $response->assertOk();
        $this->assertDatabaseMissing('watchlists', ['id' => $watchlist->id]);
    }

    public function test_user_cannot_remove_another_users_watchlist_item(): void
    {
        $otherUser = User::factory()->create();
        $jav = Jav::factory()->create();
        $watchlist = Watchlist::factory()->create([
            'user_id' => $otherUser->id,
            'jav_id' => $jav->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(route('watchlist.destroy', $watchlist));

        $response->assertForbidden();
    }

    public function test_watchlist_index_filters_by_status(): void
    {
        $jav1 = Jav::factory()->create();
        $jav2 = Jav::factory()->create();

        Watchlist::factory()->create([
            'user_id' => $this->user->id,
            'jav_id' => $jav1->id,
            'status' => 'to_watch',
        ]);

        Watchlist::factory()->create([
            'user_id' => $this->user->id,
            'jav_id' => $jav2->id,
            'status' => 'watched',
        ]);

        $response = $this->actingAs($this->user)->get(route('watchlist.index', ['status' => 'to_watch']));

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('watchlist')->count());
    }

    public function test_check_endpoint_returns_watchlist_status(): void
    {
        $jav = Jav::factory()->create();
        Watchlist::factory()->create([
            'user_id' => $this->user->id,
            'jav_id' => $jav->id,
            'status' => 'watching',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('watchlist.check', $jav->id));

        $response->assertOk();
        $response->assertJson([
            'in_watchlist' => true,
            'status' => 'watching',
        ]);
    }

    public function test_check_endpoint_returns_false_if_not_in_watchlist(): void
    {
        $jav = Jav::factory()->create();

        $response = $this->actingAs($this->user)->getJson(route('watchlist.check', $jav->id));

        $response->assertOk();
        $response->assertJson([
            'in_watchlist' => false,
        ]);
    }
}
