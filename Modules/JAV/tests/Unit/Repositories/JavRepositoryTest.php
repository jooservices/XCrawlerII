<?php

namespace Modules\JAV\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Repositories\JavRepository;
use Tests\TestCase;

class JavRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_returns_jav_builder(): void
    {
        $jav = Jav::factory()->create();

        $found = app(JavRepository::class)->query()->find($jav->id);

        $this->assertSame($jav->id, $found?->id);
    }

    public function test_query_with_relations_eager_loads_actors_and_tags(): void
    {
        $jav = Jav::factory()->create();
        $actor = Actor::factory()->create();
        $tag = Tag::factory()->create();
        $jav->actors()->attach($actor->id);
        $jav->tags()->attach($tag->id);

        $loaded = app(JavRepository::class)->queryWithRelations()->findOrFail($jav->id);

        $this->assertTrue($loaded->relationLoaded('actors'));
        $this->assertTrue($loaded->relationLoaded('tags'));
    }

    public function test_load_relations_loads_actors_and_tags(): void
    {
        $jav = Jav::factory()->create();
        $actor = Actor::factory()->create();
        $tag = Tag::factory()->create();
        $jav->actors()->attach($actor->id);
        $jav->tags()->attach($tag->id);

        $loaded = app(JavRepository::class)->loadRelations($jav);

        $this->assertTrue($loaded->relationLoaded('actors'));
        $this->assertTrue($loaded->relationLoaded('tags'));
    }
}
