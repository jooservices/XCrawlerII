<?php

namespace Modules\JAV\Tests\Feature\Dashboard;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Jobs\TagsSyncJob;
use Modules\JAV\Jobs\XcityKanaSyncJob;
use Modules\JAV\Services\ActorProfileUpsertService;
use Modules\JAV\Services\Clients\XcityClient;
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

        Queue::assertPushedOn('onejav', DailySyncJob::class, function (DailySyncJob $job): bool {
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

        Queue::assertPushedOn('xcity', XcityKanaSyncJob::class);
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
