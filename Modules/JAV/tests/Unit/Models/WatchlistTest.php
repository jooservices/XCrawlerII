<?php

namespace Modules\JAV\Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Watchlist;
use Tests\TestCase;

class WatchlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_watchlist_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $watchlist = Watchlist::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
        ]);

        $this->assertInstanceOf(User::class, $watchlist->user);
        $this->assertEquals($user->id, $watchlist->user->id);
    }

    public function test_watchlist_belongs_to_jav(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $watchlist = Watchlist::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
        ]);

        $this->assertInstanceOf(Jav::class, $watchlist->jav);
        $this->assertEquals($jav->id, $watchlist->jav->id);
    }

    public function test_status_scope_filters_by_status(): void
    {
        $user = User::factory()->create();
        $jav1 = Jav::factory()->create();
        $jav2 = Jav::factory()->create();

        Watchlist::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav1->id,
            'status' => 'to_watch',
        ]);

        Watchlist::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav2->id,
            'status' => 'watched',
        ]);

        $toWatchItems = Watchlist::status('to_watch')->get();
        $watchedItems = Watchlist::status('watched')->get();

        $this->assertEquals(1, $toWatchItems->count());
        $this->assertEquals(1, $watchedItems->count());
        $this->assertEquals('to_watch', $toWatchItems->first()->status);
        $this->assertEquals('watched', $watchedItems->first()->status);
    }

    public function test_for_user_scope_filters_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $jav = Jav::factory()->create();

        Watchlist::factory()->create([
            'user_id' => $user1->id,
            'jav_id' => $jav->id,
        ]);

        $user1Items = Watchlist::forUser($user1->id)->get();
        $user2Items = Watchlist::forUser($user2->id)->get();

        $this->assertEquals(1, $user1Items->count());
        $this->assertEquals(0, $user2Items->count());
    }
}
