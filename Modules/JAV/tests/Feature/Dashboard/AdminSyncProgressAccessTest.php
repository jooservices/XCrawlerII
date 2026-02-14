<?php

namespace Modules\JAV\Tests\Feature\Dashboard;

use App\Models\Role;
use App\Models\User;
use Modules\JAV\Tests\TestCase;

class AdminSyncProgressAccessTest extends TestCase
{
    public function test_admin_can_access_sync_progress_endpoints(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $this->actingAs($admin)
            ->get(route('jav.blade.admin.sync-progress'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('jav.admin.sync-progress.data'))
            ->assertOk()
            ->assertJsonStructure([
                'phase',
                'pending_jobs',
                'failed_jobs_24h',
                'throughput_per_min',
                'eta_seconds',
                'eta_human',
                'active_sync',
                'recent_failures',
                'updated_at',
            ]);
    }

    public function test_moderator_cannot_access_sync_progress_endpoints(): void
    {
        $moderatorRole = Role::query()->create([
            'name' => 'Moderator',
            'slug' => 'moderator',
        ]);

        $moderator = User::factory()->create();
        $moderator->assignRole($moderatorRole);

        $this->actingAs($moderator)
            ->get(route('jav.blade.admin.sync-progress'))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->get(route('jav.admin.sync-progress.data'))
            ->assertForbidden();
    }
}
