<?php

namespace Modules\JAV\Tests\Feature\Controllers\Admin\Api;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Tests\TestCase;

class SyncControllerContractTest extends TestCase
{
    public function test_admin_provider_sync_dispatch_returns_expected_shape(): void
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
            ->assertJsonStructure(['message', 'source', 'type', 'date'])
            ->assertJsonPath('source', 'onejav')
            ->assertJsonPath('type', 'daily')
            ->assertJsonPath('date', '2026-02-14');

        Queue::assertPushedOn('jav', DailySyncJob::class);
    }

    public function test_admin_provider_sync_dispatch_rejects_invalid_payload(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->postJson(route('jav.admin.provider-sync.dispatch'), [
                'source' => 'invalid-source',
                'type' => 'invalid-type',
                'date' => '14-02-2026',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['source', 'type', 'date']);
    }

    public function test_admin_provider_sync_dispatch_returns_429_when_lock_exists(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $lockKey = 'jav:sync:dispatch:onejav:daily';
        Cache::put($lockKey, 1, now()->addSeconds(30));

        $this->actingAs($admin)
            ->postJson(route('jav.admin.provider-sync.dispatch'), [
                'source' => 'onejav',
                'type' => 'daily',
                'date' => '2026-02-14',
            ])
            ->assertStatus(429)
            ->assertJsonStructure(['message']);

        Cache::forget($lockKey);
    }

    public function test_non_admin_cannot_call_admin_sync_endpoints(): void
    {
        $moderator = $this->makeUserWithRole('moderator');

        $this->actingAs($moderator)
            ->postJson(route('jav.admin.provider-sync.dispatch'), [
                'source' => 'onejav',
                'type' => 'daily',
                'date' => '2026-02-14',
            ])
            ->assertForbidden();

        $this->actingAs($moderator)
            ->postJson(route('jav.request'), [
                'source' => 'onejav',
                'type' => 'new',
            ])
            ->assertForbidden();

        $this->actingAs($moderator)
            ->getJson(route('jav.status'))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->getJson(route('jav.admin.sync-progress.data'))
            ->assertForbidden();
    }

    public function test_admin_request_and_status_and_progress_return_expected_shapes(): void
    {
        $admin = $this->makeUserWithRole('admin');
        Queue::fake();

        $this->actingAs($admin)
            ->postJson(route('jav.request'), [
                'source' => 'onejav',
                'type' => 'new',
            ])
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'progress' => ['phase', 'pending_jobs', 'failed_jobs_24h', 'updated_at'],
            ]);

        $this->actingAs($admin)
            ->getJson(route('jav.status'))
            ->assertOk()
            ->assertJsonStructure([
                'onejav' => ['new', 'popular'],
                '141jav' => ['new', 'popular'],
                'ffjav' => ['new', 'popular'],
                'progress' => ['phase', 'pending_jobs', 'failed_jobs_24h', 'updated_at'],
            ]);

        $this->actingAs($admin)
            ->getJson(route('jav.admin.sync-progress.data'))
            ->assertOk()
            ->assertJsonStructure([
                'phase',
                'pending_jobs',
                'failed_jobs_24h',
                'updated_at',
            ]);
    }

    public function test_admin_request_rejects_invalid_payload(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->postJson(route('jav.request'), [
                'source' => 'bad',
                'type' => 'bad',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['source', 'type']);
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
