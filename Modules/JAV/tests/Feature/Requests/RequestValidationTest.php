<?php

namespace Modules\JAV\Tests\Feature\Requests;

use App\Models\Role;
use App\Models\User;
use Modules\JAV\Tests\TestCase;

class RequestValidationTest extends TestCase
{
    public function test_dashboard_request_rejects_invalid_sort_direction_and_tags_mode(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('jav.vue.dashboard', [
                'sort' => 'invalid_sort',
                'direction' => 'invalid_direction',
                'tags_mode' => 'invalid_mode',
            ]))
            ->assertStatus(302)
            ->assertSessionHasErrors(['sort', 'direction', 'tags_mode']);
    }

    public function test_actors_request_rejects_invalid_age_boundaries(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('jav.vue.actors', [
                'age' => 17,
                'age_min' => 17,
                'age_max' => 120,
            ]))
            ->assertStatus(302)
            ->assertSessionHasErrors(['age', 'age_min', 'age_max']);
    }

    public function test_save_preset_request_rejects_invalid_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('jav.presets.save'), [
                'name' => str_repeat('a', 61),
                'tags_mode' => 'invalid_mode',
                'sort' => 'invalid_sort',
                'direction' => 'invalid_direction',
                'preset' => 'invalid_preset',
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors(['name', 'tags_mode', 'sort', 'direction', 'preset']);
    }

    public function test_delete_preset_request_rejects_negative_route_key(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('jav.presets.delete', ['presetKey' => -1]))
            ->assertStatus(302)
            ->assertSessionHasErrors(['presetKey']);
    }

    public function test_admin_search_quality_requests_reject_invalid_identifier_mode(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->postJson(route('jav.admin.search-quality.preview'), [
                'entity_type' => 'jav',
                'identifier' => '123',
                'identifier_mode' => 'bad-mode',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['identifier_mode']);

        $this->actingAs($admin)
            ->postJson(route('jav.admin.search-quality.publish'), [
                'entity_type' => 'jav',
                'identifier' => '123',
                'identifier_mode' => 'bad-mode',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['identifier_mode']);
    }

    public function test_admin_sync_request_rejects_idols_type_for_non_xcity_source(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->postJson(route('jav.admin.provider-sync.dispatch'), [
                'source' => 'onejav',
                'type' => 'idols',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
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
