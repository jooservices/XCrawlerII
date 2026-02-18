<?php

namespace Modules\JAV\Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorite_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $favorite = Interaction::factory()->favorite()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $favorite->user);
        $this->assertSame($user->id, $favorite->user->id);
    }

    public function test_favorite_morphs_to_jav(): void
    {
        $jav = Jav::factory()->create();
        $favorite = Interaction::factory()->forJav($jav)->favorite()->create();

        $this->assertInstanceOf(Jav::class, $favorite->item);
        $this->assertSame($jav->id, $favorite->item->id);
    }

    public function test_favorite_morphs_to_actor(): void
    {
        $actor = Actor::factory()->create();
        $favorite = Interaction::factory()->forActor($actor)->favorite()->create();

        $this->assertInstanceOf(Actor::class, $favorite->item);
        $this->assertSame($actor->id, $favorite->item->id);
    }
}
