<?php

namespace Modules\JAV\Tests\Feature\Controllers\Admin\Api;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Jobs\TagsSyncJob;
use Modules\JAV\Jobs\XcityKanaSyncJob;
use Modules\JAV\Services\ActorProfileUpsertService;
use Modules\JAV\Services\Clients\XcityClient;
use Modules\JAV\Services\XcityIdolService;
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

    public function test_admin_provider_sync_dispatch_tags_is_queued_on_jav(): void
    {
        $admin = $this->makeUserWithRole('admin');
        Queue::fake();

        $this->actingAs($admin)
            ->postJson(route('jav.admin.provider-sync.dispatch'), [
                'source' => '141jav',
                'type' => 'tags',
            ])
            ->assertOk()
            ->assertJsonPath('source', '141jav')
            ->assertJsonPath('type', 'tags')
            ->assertJsonPath('date', null);

        Queue::assertPushedOn('jav', TagsSyncJob::class, function (TagsSyncJob $job): bool {
            return $job->source === '141jav';
        });
    }

    public function test_admin_provider_sync_dispatch_xcity_idols_is_queued_on_jav_idol(): void
    {
        $admin = $this->makeUserWithRole('admin');
        Queue::fake();

        $this->app->instance(XcityIdolService::class, $this->buildRealIdolServiceFromFixtures());

        $response = $this->actingAs($admin)
            ->postJson(route('jav.admin.provider-sync.dispatch'), [
                'source' => 'xcity',
                'type' => 'idols',
            ])
            ->assertOk()
            ->assertJsonPath('source', 'xcity')
            ->assertJsonPath('type', 'idols');

        $jobs = (int) ($response->json('jobs') ?? 0);
        $this->assertGreaterThan(0, $jobs);

        Queue::assertPushedOn('jav-idol', XcityKanaSyncJob::class);
    }

    private function buildRealIdolServiceFromFixtures(): XcityIdolService
    {
        $client = \Mockery::mock(XcityClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/idol/')
            ->andReturn($this->getMockResponse('xcity_root_with_kana.html'));
        $client->shouldReceive('get')
            ->withArgs(function (string $url): bool {
                return str_contains($url, 'https://xxx.xcity.jp/idol/?kana=');
            })
            ->andReturnUsing(function (string $url) {
                if (str_contains($url, 'kana=%E3%81%8B')) {
                    return $this->getMockResponse('xcity_kana_ka_with_ini.html');
                }

                if (str_contains($url, 'kana=%E3%81%95')) {
                    return $this->getMockResponse('xcity_kana_sa_without_ini.html');
                }

                return $this->getMockResponse('xcity_kana_sa_without_ini.html');
            });

        return new XcityIdolService($client, new ActorProfileUpsertService);
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

    public function test_admin_provider_sync_dispatch_rejects_non_idols_type_for_xcity(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->postJson(route('jav.admin.provider-sync.dispatch'), [
                'source' => 'xcity',
                'type' => 'daily',
                'date' => '2026-02-14',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
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
            ->getJson(route('jav.admin.provider-sync.status'))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->getJson(route('jav.admin.sync-progress.data'))
            ->assertForbidden();
    }

    public function test_admin_provider_sync_status_and_progress_return_expected_shapes(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->getJson(route('jav.admin.provider-sync.status'))
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
