<?php

namespace Modules\JAV\Tests\Feature\Controllers\Admin\Api;

use App\Models\Role;
use App\Models\User;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Tests\TestCase;

class SearchQualityControllerContractTest extends TestCase
{
    public function test_admin_search_quality_preview_and_publish_have_expected_shapes(): void
    {
        config(['scout.driver' => 'collection']);

        $admin = $this->makeUserWithRole('admin');

        $actor = Actor::query()->create(['name' => 'Actor One']);
        $tag = Tag::query()->create(['name' => 'Tag One']);
        $jav = Jav::query()->create([
            'code' => 'ABCD-123',
            'title' => 'Sample Title',
            'url' => 'https://example.com/item',
            'image' => 'https://example.com/image.jpg',
            'source' => 'onejav',
            'date' => now(),
        ]);
        $jav->actors()->attach([$actor->id]);
        $jav->tags()->attach([$tag->id]);

        $this->actingAs($admin)
            ->postJson(route('jav.admin.search-quality.preview'), [
                'entity_type' => 'jav',
                'identifier' => $jav->uuid,
                'identifier_mode' => 'uuid',
            ])
            ->assertOk()
            ->assertJsonStructure([
                'entity',
                'search_index',
                'payload',
                'quality' => ['status', 'score', 'warnings'],
                'related',
                'previewed_at',
            ])
            ->assertJsonPath('entity.type', 'jav');

        $this->actingAs($admin)
            ->postJson(route('jav.admin.search-quality.publish'), [
                'entity_type' => 'jav',
                'identifier' => $jav->uuid,
                'identifier_mode' => 'uuid',
                'reindex_related' => true,
            ])
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'entity',
                'reindexed_count',
                'reindexed',
                'published_at',
            ])
            ->assertJsonPath('entity.type', 'jav');
    }

    public function test_admin_search_quality_rejects_invalid_and_not_found_payloads(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->postJson(route('jav.admin.search-quality.preview'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['entity_type', 'identifier']);

        $this->actingAs($admin)
            ->postJson(route('jav.admin.search-quality.preview'), [
                'entity_type' => 'jav',
                'identifier' => 'not-exist',
                'identifier_mode' => 'uuid',
            ])
            ->assertStatus(404)
            ->assertJsonPath('message', 'Record not found.');

        $this->actingAs($admin)
            ->postJson(route('jav.admin.search-quality.publish'), [
                'entity_type' => 'actor',
                'identifier' => 'not-exist',
                'identifier_mode' => 'uuid',
            ])
            ->assertStatus(404)
            ->assertJsonPath('message', 'Record not found.');
    }

    public function test_non_admin_cannot_call_admin_search_quality_endpoints(): void
    {
        $moderator = $this->makeUserWithRole('moderator');

        $this->actingAs($moderator)
            ->postJson(route('jav.admin.search-quality.preview'), [
                'entity_type' => 'jav',
                'identifier' => 'x',
            ])
            ->assertForbidden();

        $this->actingAs($moderator)
            ->postJson(route('jav.admin.search-quality.publish'), [
                'entity_type' => 'jav',
                'identifier' => 'x',
            ])
            ->assertForbidden();
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
