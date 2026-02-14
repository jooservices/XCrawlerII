<?php

namespace Modules\JAV\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Repositories\ActorRepository;
use Tests\TestCase;

class ActorRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggestions_returns_trimmed_names(): void
    {
        Actor::factory()->create(['name' => '  Alice  ']);
        Actor::factory()->create(['name' => 'Bob']);

        $result = app(ActorRepository::class)->suggestions();

        $this->assertContains('Alice', $result);
        $this->assertContains('Bob', $result);
    }

    public function test_actor_movies_returns_paginated_movies_for_actor_ordered_by_date_desc(): void
    {
        $actor = Actor::factory()->create();
        $newer = Jav::factory()->create(['date' => now()]);
        $older = Jav::factory()->create(['date' => now()->subDay()]);
        $actor->javs()->attach([$older->id, $newer->id]);

        $paginator = app(ActorRepository::class)->actorMovies($actor, 30);

        $this->assertCount(2, $paginator->items());
        $this->assertSame($newer->id, $paginator->items()[0]->id);
    }

    public function test_unique_column_values_filters_null_and_empty_values(): void
    {
        Actor::factory()->create(['xcity_blood_type' => 'A']);
        Actor::factory()->create(['xcity_blood_type' => 'B']);
        Actor::factory()->create(['xcity_blood_type' => '']);
        Actor::factory()->create(['xcity_blood_type' => null]);

        $values = app(ActorRepository::class)->uniqueColumnValues('xcity_blood_type');

        $this->assertContains('A', $values);
        $this->assertContains('B', $values);
        $this->assertNotContains('', $values);
    }
}
