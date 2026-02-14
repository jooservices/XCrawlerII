<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use App\Models\User;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Watchlist;
use Modules\JAV\Tests\TestCase;

class WatchlistControllerContractTest extends TestCase
{
    public function test_watchlist_api_contract_auth_validation_and_ownership(): void
    {
        $jav = Jav::factory()->create();
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->postJson(route('jav.api.watchlist.store'), [
            'jav_id' => $jav->id,
        ])->assertUnauthorized();

        $this->actingAs($user)
            ->postJson(route('jav.api.watchlist.store'), [
                'jav_id' => $jav->id,
                'status' => 'to_watch',
            ])
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'watchlist'])
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->postJson(route('jav.api.watchlist.store'), [
                'status' => 'to_watch',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['jav_id']);

        $myWatchlist = Watchlist::query()->where('user_id', $user->id)->where('jav_id', $jav->id)->firstOrFail();
        $otherWatchlist = Watchlist::factory()->create([
            'user_id' => $other->id,
            'jav_id' => $jav->id,
            'status' => 'to_watch',
        ]);

        $this->actingAs($user)
            ->putJson(route('jav.api.watchlist.update', $myWatchlist), [
                'status' => 'watching',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('watchlist.status', 'watching');

        $this->actingAs($user)
            ->putJson(route('jav.api.watchlist.update', $otherWatchlist), [
                'status' => 'watching',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->deleteJson(route('jav.api.watchlist.destroy', $otherWatchlist))
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->actingAs($user)
            ->getJson(route('jav.api.watchlist.check', $jav->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('in_watchlist', true);

        $this->actingAs($user)
            ->deleteJson(route('jav.api.watchlist.destroy', $myWatchlist))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->getJson(route('jav.api.watchlist.check', $jav->id))
            ->assertOk()
            ->assertJsonPath('in_watchlist', false);
    }
}
