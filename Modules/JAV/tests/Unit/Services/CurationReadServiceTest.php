<?php

namespace Modules\JAV\Tests\Unit\Services;

use App\Models\User;
use Modules\Core\Models\CuratedItem;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Tag;
use Modules\JAV\Services\CurationReadService;
use Modules\JAV\Tests\TestCase;

class CurationReadServiceTest extends TestCase
{
    public function test_decorate_actors_with_featured_state_sets_attributes_on_models(): void
    {
        $actorA = Actor::factory()->create();
        $actorB = Actor::factory()->create();
        $user = User::factory()->create();

        CuratedItem::query()->create([
            'item_type' => 'actor',
            'item_id' => $actorA->id,
            'curation_type' => 'featured',
            'user_id' => $user->id,
        ]);

        $service = new CurationReadService;
        $service->decorateActorsWithFeaturedState(collect([$actorA, $actorB]));

        $this->assertTrue($actorA->is_featured);
        $this->assertNotNull($actorA->featured_curation_uuid);
        $this->assertFalse($actorB->is_featured);
        $this->assertNull($actorB->featured_curation_uuid);
    }

    public function test_decorate_actors_with_featured_state_includes_fields_in_to_array(): void
    {
        $actor = Actor::factory()->create();
        $user = User::factory()->create();
        $curation = CuratedItem::query()->create([
            'item_type' => 'actor',
            'item_id' => $actor->id,
            'curation_type' => 'featured',
            'user_id' => $user->id,
        ]);

        $service = new CurationReadService;
        $service->decorateActorsWithFeaturedState(collect([$actor]));

        $array = $actor->toArray();
        $this->assertArrayHasKey('is_featured', $array, 'Actor toArray() must include is_featured for Inertia/JSON');
        $this->assertArrayHasKey('featured_curation_uuid', $array, 'Actor toArray() must include featured_curation_uuid for Inertia/JSON');
        $this->assertTrue($array['is_featured']);
        $this->assertSame($curation->uuid, $array['featured_curation_uuid']);
    }

    public function test_decorate_actors_with_featured_state_handles_array_items(): void
    {
        $actor = Actor::factory()->create();
        $user = User::factory()->create();
        CuratedItem::query()->create([
            'item_type' => 'actor',
            'item_id' => $actor->id,
            'curation_type' => 'featured',
            'user_id' => $user->id,
        ]);

        $actorList = collect([['id' => $actor->id, 'name' => 'Test']]);
        $service = new CurationReadService;
        $service->decorateActorsWithFeaturedState($actorList);

        $decorated = $actorList->get(0);
        $this->assertIsArray($decorated);
        $this->assertArrayHasKey('is_featured', $decorated);
        $this->assertTrue($decorated['is_featured']);
        $this->assertArrayHasKey('featured_curation_uuid', $decorated);
        $this->assertNotNull($decorated['featured_curation_uuid']);
    }

    public function test_decorate_tags_with_featured_state_sets_attributes_on_models(): void
    {
        $tagA = Tag::factory()->create();
        $tagB = Tag::factory()->create();
        $user = User::factory()->create();

        CuratedItem::query()->create([
            'item_type' => 'tag',
            'item_id' => $tagA->id,
            'curation_type' => 'featured',
            'user_id' => $user->id,
        ]);

        $service = new CurationReadService;
        $service->decorateTagsWithFeaturedState(collect([$tagA, $tagB]));

        $this->assertTrue($tagA->is_featured);
        $this->assertNotNull($tagA->featured_curation_uuid);
        $this->assertFalse($tagB->is_featured);
        $this->assertNull($tagB->featured_curation_uuid);
    }
}
