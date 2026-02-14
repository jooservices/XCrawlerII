<?php

namespace Modules\JAV\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\ActorProfileSource;
use Tests\TestCase;

class ActorProfileSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_source_belongs_to_actor(): void
    {
        $actor = Actor::factory()->create();
        $source = ActorProfileSource::factory()->create(['actor_id' => $actor->id]);

        $this->assertInstanceOf(Actor::class, $source->actor);
        $this->assertSame($actor->id, $source->actor->id);
    }
}
