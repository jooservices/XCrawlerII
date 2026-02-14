<?php

namespace Modules\JAV\Tests\Feature\Dashboard;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Tests\TestCase;

class AdminProviderSyncTest extends TestCase
{
    public function test_admin_can_access_provider_sync_page(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->get(route('jav.vue.admin.provider-sync'))
            ->assertOk();
    }

    public function test_moderator_cannot_access_provider_sync_page(): void
    {
        $moderator = $this->makeUserWithRole('moderator');

        $this->actingAs($moderator)
            ->get(route('jav.vue.admin.provider-sync'))
            ->assertForbidden();
    }

    public function test_admin_can_dispatch_provider_sync_via_ajax(): void
    {
        $admin = $this->makeUserWithRole('admin');
        Queue::fake();

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

        Queue::assertPushedOn('jav', DailySyncJob::class, function (DailySyncJob $job): bool {
            return $job->source === 'onejav'
                && $job->date === '2026-02-14'
                && $job->page === 1;
        });
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
