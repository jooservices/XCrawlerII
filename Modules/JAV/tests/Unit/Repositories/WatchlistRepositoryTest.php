<?php

namespace Modules\JAV\Tests\Unit\Repositories;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Watchlist;
use Modules\JAV\Repositories\WatchlistRepository;
use Tests\TestCase;

class WatchlistRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyed_by_jav_id_for_user_and_jav_ids_returns_matching_rows(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $jav1 = Jav::factory()->create();
        $jav2 = Jav::factory()->create();

        $watchlist1 = Watchlist::factory()->create(['user_id' => $user->id, 'jav_id' => $jav1->id]);
        Watchlist::factory()->create(['user_id' => $otherUser->id, 'jav_id' => $jav2->id]);

        $rows = app(WatchlistRepository::class)->keyedByJavIdForUserAndJavIds($user->id, collect([$jav1->id, $jav2->id]));

        $this->assertTrue($rows->has($jav1->id));
        $this->assertFalse($rows->has($jav2->id));
        $this->assertSame($watchlist1->id, $rows->get($jav1->id)->id);
    }
}
