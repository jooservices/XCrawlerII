<?php

namespace Modules\JAV\Tests\Unit\Repositories;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Repositories\InteractionRepository;
use Tests\TestCase;

class FavoriteRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginate_for_user_returns_only_user_favorites(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Interaction::factory()->forJav(Jav::factory()->create())->favorite()->create(['user_id' => $user->id]);
        Interaction::factory()->forJav(Jav::factory()->create())->favorite()->create(['user_id' => $otherUser->id]);

        $page = app(InteractionRepository::class)->paginateFavoritesForUser($user->id, 30);

        $this->assertCount(1, $page->items());
        $this->assertSame($user->id, $page->items()[0]->user_id);
    }

    public function test_is_jav_liked_by_user_checks_jav_favorites(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        Interaction::factory()->forJav($jav)->favorite()->create(['user_id' => $user->id]);

        $repository = app(InteractionRepository::class);
        $this->assertTrue($repository->isJavLikedByUser($jav, $user->id));
        $this->assertFalse($repository->isJavLikedByUser($jav, User::factory()->create()->id));
    }

    public function test_liked_jav_ids_for_user_and_jav_ids_returns_flipped_collection(): void
    {
        $user = User::factory()->create();
        $jav1 = Jav::factory()->create();
        $jav2 = Jav::factory()->create();
        $actor = Actor::factory()->create();

        Interaction::factory()->forJav($jav1)->favorite()->create(['user_id' => $user->id]);
        Interaction::factory()->forActor($actor)->favorite()->create(['user_id' => $user->id]);

        $liked = app(InteractionRepository::class)->likedJavIdsForUserAndJavIds($user->id, collect([$jav1->id, $jav2->id]));

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

        Interaction::factory()->forTag($tagA)->favorite()->create(['user_id' => $user->id]);
        Interaction::factory()->forTag($tagB)->favorite()->create(['user_id' => $user->id]);
        Interaction::factory()->forTag($tagA)->favorite()->create(['user_id' => $otherUser->id]);
        Interaction::factory()->forActor($actor)->favorite()->create(['user_id' => $user->id]);

        $names = app(InteractionRepository::class)->preferredTagNamesForUser($user->id);

        $this->assertEqualsCanonicalizing(['Tag A', 'Tag B'], $names->all());
    }
}
