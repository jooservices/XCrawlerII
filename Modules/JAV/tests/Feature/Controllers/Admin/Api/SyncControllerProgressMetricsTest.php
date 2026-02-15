<?php

namespace Modules\JAV\Tests\Feature\Controllers\Admin\Api;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\JAV\Tests\TestCase;

class SyncControllerProgressMetricsTest extends TestCase
{
    public function test_sync_progress_phase_is_processing_when_pending_jobs_exist(): void
    {
        $admin = $this->makeUserWithRole('admin');

        DB::table('jobs')->insert([
            'queue' => 'jav',
            'payload' => '{}',
            'attempts' => 0,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $this->actingAs($admin)
            ->getJson(route('jav.admin.sync-progress.data'))
            ->assertOk()
            ->assertJsonPath('phase', 'processing')
            ->assertJsonStructure([
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

    public function test_sync_progress_phase_is_completed_when_no_pending_jobs_but_active_sync_exists(): void
    {
        $admin = $this->makeUserWithRole('admin');

        Cache::put('jav:sync:active', [
            'provider' => 'onejav',
            'type' => 'daily',
            'started_at' => now()->toIso8601String(),
        ], now()->addMinutes(10));

        $this->actingAs($admin)
            ->getJson(route('jav.admin.sync-progress.data'))
            ->assertOk()
            ->assertJsonPath('phase', 'completed')
            ->assertJsonPath('active_sync.provider', 'onejav')
            ->assertJsonPath('active_sync.type', 'daily');
    }

    public function test_sync_progress_computes_throughput_and_eta_when_pending_jobs_drop(): void
    {
        $admin = $this->makeUserWithRole('admin');

        DB::table('jobs')->insert([
            'queue' => 'jav',
            'payload' => '{}',
            'attempts' => 0,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        Cache::put('jav:sync:metrics', [
            'last_pending' => 6,
            'last_ts' => now()->subMinutes(2)->timestamp,
            'rate_per_min' => null,
        ], now()->addHours(1));

        $this->actingAs($admin)
            ->getJson(route('jav.admin.sync-progress.data'))
            ->assertOk()
            ->assertJsonPath('pending_jobs', 1)
            ->assertJson(fn ($json) => $json
                ->whereType('throughput_per_min', 'double')
                ->whereType('eta_seconds', 'integer')
                ->whereType('eta_human', 'string')
                ->etc()
            );
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
