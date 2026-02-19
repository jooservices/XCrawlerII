<?php

namespace Modules\Core\Tests\Feature\Http;

use App\Models\Role;
use App\Models\User;
use Modules\Core\Models\CuratedItem;
use Modules\Core\Tests\TestCase;
use Modules\JAV\Models\Jav;

class CurationApiTest extends TestCase
{
    public function test_guest_cannot_access_curation_endpoints(): void
    {
        $this->getJson(route('api.curations.index'))->assertUnauthorized();
        $this->postJson(route('api.curations.store'), [])->assertUnauthorized();
    }

    public function test_authenticated_non_admin_can_list_but_cannot_write_curations(): void
    {
        $user = User::factory()->create();
        $movie = Jav::factory()->create();

        $this->actingAs($user)
            ->getJson(route('api.curations.index'))
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('api.curations.store'), [
                'item_type' => 'jav',
                'item_id' => $movie->id,
                'curation_type' => 'featured',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_create_list_filter_and_delete_curations(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::factory()->create(['slug' => 'admin', 'name' => 'Administrator']);
        $admin->assignRole($adminRole);

        $movie = Jav::factory()->create();

        $store = $this->actingAs($admin)
            ->postJson(route('api.curations.store'), [
                'item_type' => 'jav',
                'item_id' => $movie->id,
                'curation_type' => 'featured',
                'position' => 5,
                'meta' => ['source' => 'test'],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.item_type', 'jav')
            ->assertJsonPath('data.item_id', $movie->id)
            ->assertJsonPath('data.curation_type', 'featured');

        $uuid = (string) $store->json('data.uuid');

        $this->assertDatabaseHas('curated_items', [
            'uuid' => $uuid,
            'item_type' => 'jav',
            'item_id' => $movie->id,
            'curation_type' => 'featured',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->getJson(route('api.curations.index', [
                'curation_type' => 'featured',
                'item_type' => 'jav',
                'item_id' => $movie->id,
            ]))
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $uuid);

        $this->actingAs($admin)
            ->deleteJson(route('api.curations.destroy', ['curation' => $uuid]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('curated_items', [
            'uuid' => $uuid,
        ]);
    }

    public function test_admin_create_is_idempotent_for_same_type_and_item(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::factory()->create(['slug' => 'admin', 'name' => 'Administrator']);
        $admin->assignRole($adminRole);

        $movie = Jav::factory()->create();

        $first = $this->actingAs($admin)
            ->postJson(route('api.curations.store'), [
                'item_type' => 'jav',
                'item_id' => $movie->id,
                'curation_type' => 'featured',
            ])
            ->assertOk();

        $second = $this->actingAs($admin)
            ->postJson(route('api.curations.store'), [
                'item_type' => 'jav',
                'item_id' => $movie->id,
                'curation_type' => 'featured',
            ])
            ->assertOk();

        $this->assertSame(
            (string) $first->json('data.uuid'),
            (string) $second->json('data.uuid')
        );

        $count = CuratedItem::query()
            ->where('item_type', 'jav')
            ->where('item_id', $movie->id)
            ->where('curation_type', 'featured')
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_validation_rejects_invalid_payloads(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::factory()->create(['slug' => 'admin', 'name' => 'Administrator']);
        $admin->assignRole($adminRole);

        $movie = Jav::factory()->create();

        $this->actingAs($admin)
            ->postJson(route('api.curations.store'), [
                'item_type' => 'javx',
                'item_id' => $movie->id,
                'curation_type' => 'featured',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['item_type']);

        $this->actingAs($admin)
            ->postJson(route('api.curations.store'), [
                'item_type' => 'jav',
                'item_id' => $movie->id,
                'curation_type' => 'invalid_type',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['curation_type']);

        $this->actingAs($admin)
            ->postJson(route('api.curations.store'), [
                'item_type' => 'jav',
                'item_id' => 999999,
                'curation_type' => 'featured',
            ])
            ->assertStatus(404);
    }
}
