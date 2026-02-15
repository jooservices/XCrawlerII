<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use App\Models\User;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Tag;
use Modules\JAV\Tests\TestCase;

class LibraryControllerEdgeTest extends TestCase
{
    public function test_toggle_like_returns_not_found_for_unknown_target_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.toggle-like'), [
                'id' => 999999,
                'type' => 'actor',
            ])
            ->assertNotFound();
    }

    public function test_toggle_like_persists_and_removes_actor_favorite_records(): void
    {
        $user = User::factory()->create();
        $actor = Actor::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.toggle-like'), [
                'id' => $actor->id,
                'type' => 'actor',
            ])
            ->assertOk()
            ->assertJsonPath('liked', true);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => Actor::class,
            'favoritable_id' => $actor->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('jav.toggle-like'), [
                'id' => $actor->id,
                'type' => 'actor',
            ])
            ->assertOk()
            ->assertJsonPath('liked', false);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => Actor::class,
            'favoritable_id' => $actor->id,
        ]);
    }

    public function test_toggle_like_persists_tag_favorite_record(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.toggle-like'), [
                'id' => $tag->id,
                'type' => 'tag',
            ])
            ->assertOk()
            ->assertJsonPath('liked', true);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => Tag::class,
            'favoritable_id' => $tag->id,
        ]);

        $this->assertSame(
            1,
            Favorite::query()
                ->where('user_id', $user->id)
                ->where('favoritable_type', Tag::class)
                ->where('favoritable_id', $tag->id)
                ->count()
        );
    }
}
