<?php

namespace Modules\JAV\Tests\Feature\Controllers\Admin\Api;

use App\Models\Role;
use App\Models\User;
use Modules\JAV\Tests\TestCase;

class AnalyticsControllerTest extends TestCase
{
    public function test_distribution_requires_genre_returns_422_when_empty(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $this->actingAs($admin);

        $this->getJson(route('jav.admin.analytics.distribution', ['genre' => '']))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Genre is required.');
    }

    public function test_distribution_returns_422_when_genre_missing(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $this->actingAs($admin);

        $this->getJson(route('jav.admin.analytics.distribution'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Genre is required.');
    }

    public function test_actor_insights_requires_actor_uuid_returns_422_when_empty(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $this->actingAs($admin);

        $this->getJson(route('jav.admin.analytics.actor-insights', ['actor_uuid' => '']))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Actor UUID is required.');
    }

    public function test_actor_insights_returns_422_when_actor_uuid_missing(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $this->actingAs($admin);

        $this->getJson(route('jav.admin.analytics.actor-insights'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Actor UUID is required.');
    }

    public function test_association_requires_segment_value_returns_422_when_empty(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $this->actingAs($admin);

        $this->getJson(route('jav.admin.analytics.association', ['segment_value' => '']))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Segment value is required.');
    }

    public function test_admin_analytics_endpoints_require_authentication(): void
    {
        $this->assertGuest();

        $this->getJson(route('jav.admin.analytics.distribution', ['genre' => 'drama']))
            ->assertStatus(401);
        $this->getJson(route('jav.admin.analytics.overview'))
            ->assertStatus(401);
        $this->getJson(route('jav.admin.analytics.quality'))
            ->assertStatus(401);
    }

    private function makeUserWithRole(string $roleSlug): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => ucfirst($roleSlug)]
        );

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
