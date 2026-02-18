<?php

namespace Modules\JAV\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_has_javs_relationship(): void
    {
        $tag = Tag::factory()->create();
        $jav = Jav::factory()->create();
        $tag->javs()->attach($jav->id);

        $this->assertTrue($tag->fresh()->javs->contains($jav));
    }

    public function test_tag_has_favorites_relationship(): void
    {
        $tag = Tag::factory()->create();
        $favorite = Interaction::factory()->forTag($tag)->favorite()->create();

        $this->assertTrue($tag->favorites->contains($favorite));
    }
}
