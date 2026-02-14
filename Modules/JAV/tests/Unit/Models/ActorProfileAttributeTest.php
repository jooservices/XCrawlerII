<?php

namespace Modules\JAV\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\ActorProfileAttribute;
use Tests\TestCase;

class ActorProfileAttributeTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_attribute_belongs_to_actor(): void
    {
        $actor = Actor::factory()->create();
        $attribute = ActorProfileAttribute::factory()->create(['actor_id' => $actor->id]);

        $this->assertInstanceOf(Actor::class, $attribute->actor);
        $this->assertSame($actor->id, $attribute->actor->id);
    }
}
