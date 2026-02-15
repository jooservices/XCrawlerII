<?php

namespace Modules\JAV\Tests\Feature\Controllers\Admin\Api;

use App\Models\Role;
use App\Models\User;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Tests\TestCase;

class SearchQualityControllerAdvancedTest extends TestCase
{
    public function test_preview_resolves_numeric_identifier_in_auto_mode(): void
    {
        config(['scout.driver' => 'collection']);

        $admin = $this->makeUserWithRole('admin');
        $jav = Jav::factory()->create([
            'source' => 'onejav',
            'code' => 'ABP-123',
        ]);

        $this->actingAs($admin)
            ->postJson(route('jav.admin.search-quality.preview'), [
                'entity_type' => 'jav',
                'identifier' => (string) $jav->id,
                'identifier_mode' => 'auto',
            ])
            ->assertOk()
            ->assertJsonPath('entity.id', $jav->id)
            ->assertJsonPath('entity.type', 'jav');
    }

    public function test_publish_without_reindex_related_only_reindexes_requested_entity(): void
    {
        config(['scout.driver' => 'collection']);

        $admin = $this->makeUserWithRole('admin');

        $actor = Actor::factory()->create(['name' => 'Actor A']);
        $tag = Tag::factory()->create(['name' => 'Tag A']);
        $jav = Jav::factory()->create([
            'source' => 'onejav',
            'code' => 'IPX-001',
        ]);
        $jav->actors()->attach($actor->id);
        $jav->tags()->attach($tag->id);

        $this->actingAs($admin)
            ->postJson(route('jav.admin.search-quality.publish'), [
                'entity_type' => 'jav',
                'identifier' => $jav->uuid,
                'identifier_mode' => 'uuid',
                'reindex_related' => false,
            ])
            ->assertOk()
            ->assertJsonPath('entity.id', $jav->id)
            ->assertJsonPath('reindexed_count', 1)
            ->assertJsonCount(1, 'reindexed');
    }

    public function test_publish_actor_with_reindex_related_reindexes_linked_movies(): void
    {
        config(['scout.driver' => 'collection']);

        $admin = $this->makeUserWithRole('admin');

        $actor = Actor::factory()->create(['name' => 'Actor Main']);
        $jav1 = Jav::factory()->create(['source' => 'onejav']);
        $jav2 = Jav::factory()->create(['source' => 'ffjav']);
        $actor->javs()->attach([$jav1->id, $jav2->id]);

        $this->actingAs($admin)
            ->postJson(route('jav.admin.search-quality.publish'), [
                'entity_type' => 'actor',
                'identifier' => $actor->uuid,
                'identifier_mode' => 'uuid',
                'reindex_related' => true,
            ])
            ->assertOk()
            ->assertJsonPath('entity.id', $actor->id)
            ->assertJsonPath('reindexed_count', 3);
    }

    private function makeUserWithRole(string $roleSlug): User
    {
        $role = Role::query()->create([
            'name' => ucfirst($roleSlug),
            'slug' => $roleSlug,
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
