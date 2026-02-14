<?php

namespace Modules\JAV\Tests\Feature\Dashboard;

use App\Models\Role;
use App\Models\User;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Tests\TestCase;

class AdminSearchQualityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['scout.driver' => 'collection']);
    }

    public function test_admin_can_preview_and_publish_jav_document(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $actor = Actor::query()->create([
            'name' => 'Actor One',
        ]);
        $tag = Tag::query()->create([
            'name' => 'Tag One',
        ]);
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

        $previewResponse = $this->actingAs($admin)
            ->postJson(route('jav.admin.search-quality.preview'), [
                'entity_type' => 'jav',
                'identifier' => $jav->uuid,
                'identifier_mode' => 'uuid',
            ]);

        $previewResponse
            ->assertOk()
            ->assertJsonPath('entity.type', 'jav')
            ->assertJsonPath('entity.uuid', $jav->uuid)
            ->assertJsonPath('quality.status', 'healthy');

        $publishResponse = $this->actingAs($admin)
            ->postJson(route('jav.admin.search-quality.publish'), [
                'entity_type' => 'jav',
                'identifier' => $jav->uuid,
                'identifier_mode' => 'uuid',
                'reindex_related' => true,
            ]);

        $publishResponse
            ->assertOk()
            ->assertJsonPath('entity.type', 'jav')
            ->assertJsonPath('entity.uuid', $jav->uuid)
            ->assertJsonPath('reindexed_count', 3);
    }

    public function test_moderator_cannot_access_search_quality_page(): void
    {
        $moderator = $this->makeUserWithRole('moderator');

        $this->actingAs($moderator)
            ->get(route('jav.blade.admin.search-quality.index'))
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
