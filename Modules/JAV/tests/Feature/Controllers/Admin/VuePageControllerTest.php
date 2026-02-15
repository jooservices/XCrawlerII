<?php

namespace Modules\JAV\Tests\Feature\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use Modules\JAV\Tests\Feature\Controllers\Concerns\InteractsWithInertiaPage;
use Modules\JAV\Tests\TestCase;

class VuePageControllerTest extends TestCase
{
    use InteractsWithInertiaPage;

    public function test_admin_can_render_admin_vue_pages_with_expected_components(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $cases = [
            ['route' => route('jav.vue.admin.analytics'), 'component' => 'Admin/Analytics', 'props' => ['days', 'totals', 'syncHealth']],
            ['route' => route('jav.vue.admin.sync-progress'), 'component' => 'Admin/SyncProgress', 'props' => []],
            ['route' => route('jav.vue.admin.search-quality'), 'component' => 'Admin/SearchQuality', 'props' => []],
            ['route' => route('jav.vue.admin.provider-sync'), 'component' => 'Admin/ProviderSync', 'props' => []],
        ];

        foreach ($cases as $case) {
            $response = $this->actingAs($admin)->get($case['route']);
            $this->assertInertiaPage($response, $case['component'], $case['props']);
        }
    }

    public function test_non_admin_cannot_access_admin_vue_pages(): void
    {
        $moderator = $this->makeUserWithRole('moderator');

        $this->actingAs($moderator)
            ->get(route('jav.vue.admin.analytics'))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get(route('jav.vue.admin.sync-progress'))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get(route('jav.vue.admin.search-quality'))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get(route('jav.vue.admin.provider-sync'))
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
