<?php

namespace Modules\JAV\Tests\Feature\Dashboard;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Modules\JAV\Tests\TestCase;

class AdminProviderSyncTest extends TestCase
{
    public function test_admin_can_access_provider_sync_page(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->get(route('jav.admin.provider-sync.index'))
            ->assertOk();
    }

    public function test_moderator_cannot_access_provider_sync_page(): void
    {
        $moderator = $this->makeUserWithRole('moderator');

        $this->actingAs($moderator)
            ->get(route('jav.admin.provider-sync.index'))
            ->assertForbidden();
    }

    public function test_admin_can_dispatch_provider_sync_via_ajax(): void
    {
        $admin = $this->makeUserWithRole('admin');

        Artisan::shouldReceive('call')
            ->once()
            ->with('jav:sync', \Mockery::on(function (array $payload): bool {
                return $payload['provider'] === 'onejav'
                    && $payload['--type'] === 'daily'
                    && $payload['--date'] === '2026-02-14';
            }))
            ->andReturn(0);

        $this->actingAs($admin)
            ->postJson(route('jav.admin.provider-sync.dispatch'), [
                'source' => 'onejav',
                'type' => 'daily',
                'date' => '2026-02-14',
            ])
            ->assertOk()
            ->assertJsonPath('source', 'onejav')
            ->assertJsonPath('type', 'daily')
            ->assertJsonPath('date', '2026-02-14');
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
