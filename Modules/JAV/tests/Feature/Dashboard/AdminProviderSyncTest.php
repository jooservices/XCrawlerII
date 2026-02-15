<?php

namespace Modules\JAV\Tests\Feature\Dashboard;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Jobs\TagsSyncJob;
use Modules\JAV\Jobs\XcityKanaSyncJob;
use Modules\JAV\Services\XcityIdolService;
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

    public function test_admin_can_dispatch_tags_sync_via_ajax(): void
    {
        $admin = $this->makeUserWithRole('admin');
        Queue::fake();

        $this->actingAs($admin)
            ->postJson(route('jav.admin.provider-sync.dispatch'), [
                'source' => 'ffjav',
                'type' => 'tags',
            ])
            ->assertOk()
            ->assertJsonPath('source', 'ffjav')
            ->assertJsonPath('type', 'tags')
            ->assertJsonPath('date', null);

        Queue::assertPushedOn('jav', TagsSyncJob::class, function (TagsSyncJob $job): bool {
            return $job->source === 'ffjav';
        });
    }

    public function test_admin_can_dispatch_xcity_idols_sync_via_ajax(): void
    {
        $admin = $this->makeUserWithRole('admin');
        Queue::fake();

        $service = \Mockery::mock(XcityIdolService::class);
        $service->shouldReceive('seedKanaUrls')->once()->andReturn([
            'seed-a' => 'https://xxx.xcity.jp/idol/?kana=a',
        ]);
        $service->shouldReceive('pickSeedsForDispatch')->once()->andReturn(collect([
            ['seed_key' => 'seed-a', 'seed_url' => 'https://xxx.xcity.jp/idol/?kana=a'],
        ]));
        $this->app->instance(XcityIdolService::class, $service);

        $this->actingAs($admin)
            ->postJson(route('jav.admin.provider-sync.dispatch'), [
                'source' => 'xcity',
                'type' => 'idols',
            ])
            ->assertOk()
            ->assertJsonPath('source', 'xcity')
            ->assertJsonPath('type', 'idols')
            ->assertJsonPath('jobs', 1);

        Queue::assertPushedOn('jav', XcityKanaSyncJob::class);
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
