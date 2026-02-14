<?php

namespace Modules\JAV\Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Jav;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorite_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $favorite = Favorite::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $favorite->user);
        $this->assertSame($user->id, $favorite->user->id);
    }

    public function test_favorite_morphs_to_jav(): void
    {
        $jav = Jav::factory()->create();
        $favorite = Favorite::factory()->create([
            'favoritable_type' => Jav::class,
            'favoritable_id' => $jav->id,
        ]);

        $this->assertInstanceOf(Jav::class, $favorite->favoritable);
        $this->assertSame($jav->id, $favorite->favoritable->id);
    }

    public function test_favorite_morphs_to_actor(): void
    {
        $actor = Actor::factory()->create();
        $favorite = Favorite::factory()->create([
            'favoritable_type' => Actor::class,
            'favoritable_id' => $actor->id,
        ]);

        $this->assertInstanceOf(Actor::class, $favorite->favoritable);
        $this->assertSame($actor->id, $favorite->favoritable->id);
    }
}
