<?php

namespace Modules\JAV\Tests\Unit\Repositories;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Repositories\FavoriteRepository;
use Tests\TestCase;

class FavoriteRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginate_for_user_returns_only_user_favorites(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Favorite::factory()->create(['user_id' => $user->id]);
        Favorite::factory()->create(['user_id' => $otherUser->id]);

        $page = app(FavoriteRepository::class)->paginateForUser($user->id, 30);

        $this->assertCount(1, $page->items());
        $this->assertSame($user->id, $page->items()[0]->user_id);
    }

    public function test_is_jav_liked_by_user_checks_jav_favorites(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Jav::class,
            'favoritable_id' => $jav->id,
        ]);

        $repository = app(FavoriteRepository::class);
        $this->assertTrue($repository->isJavLikedByUser($jav, $user->id));
        $this->assertFalse($repository->isJavLikedByUser($jav, User::factory()->create()->id));
    }

    public function test_liked_jav_ids_for_user_and_jav_ids_returns_flipped_collection(): void
    {
        $user = User::factory()->create();
        $jav1 = Jav::factory()->create();
        $jav2 = Jav::factory()->create();
        $actor = Actor::factory()->create();

        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Jav::class,
            'favoritable_id' => $jav1->id,
        ]);
        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Actor::class,
            'favoritable_id' => $actor->id,
        ]);

        $liked = app(FavoriteRepository::class)->likedJavIdsForUserAndJavIds($user->id, collect([$jav1->id, $jav2->id]));

        $this->assertTrue($liked->has($jav1->id));
        $this->assertFalse($liked->has($jav2->id));
    }

    public function test_preferred_tag_names_for_user_returns_unique_tag_names(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $tagA = Tag::factory()->create(['name' => 'Tag A']);
        $tagB = Tag::factory()->create(['name' => 'Tag B']);
        $actor = Actor::factory()->create();

        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Tag::class,
            'favoritable_id' => $tagA->id,
        ]);
        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Tag::class,
            'favoritable_id' => $tagB->id,
        ]);
        Favorite::factory()->create([
            'user_id' => $otherUser->id,
            'favoritable_type' => Tag::class,
            'favoritable_id' => $tagA->id,
        ]);
        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_type' => Actor::class,
            'favoritable_id' => $actor->id,
        ]);

        $names = app(FavoriteRepository::class)->preferredTagNamesForUser($user->id);

        $this->assertEqualsCanonicalizing(['Tag A', 'Tag B'], $names->all());
    }
}
