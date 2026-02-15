<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserLikeNotification;
use Tests\TestCase;

class HandleInertiaRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_returns_guest_shape_with_zero_notifications(): void
    {
        $middleware = new HandleInertiaRequests;
        $request = Request::create('/jav/dashboard', 'GET');
        $request->setUserResolver(static fn () => null);

        $shared = $middleware->share($request);

        $this->assertNull($shared['auth']['user']);
        $this->assertSame(0, $shared['notifications']['count']());
        $this->assertSame([], $shared['notifications']['items']());
    }

    public function test_share_returns_authenticated_user_roles_permissions_and_notifications(): void
    {
        $permission = Permission::factory()->create(['slug' => 'view-users']);
        $role = Role::factory()->create(['slug' => 'admin']);
        $role->permissions()->attach($permission->id);

        $user = User::factory()->create([
            'avatar_path' => null,
            'preferences' => ['show_cover' => true],
        ]);
        $user->roles()->attach($role->id);

        $jav = Jav::factory()->create([
            'title' => 'Sample Jav',
            'code' => 'SAMPLE-001',
        ]);

        UserLikeNotification::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'title' => 'Liked',
            'payload' => ['type' => 'like'],
            'read_at' => null,
        ]);

        $middleware = new HandleInertiaRequests;
        $request = Request::create('/jav/dashboard', 'GET');
        $request->setUserResolver(fn () => $user->fresh('roles.permissions'));

        $shared = $middleware->share($request);

        $authUser = $shared['auth']['user'];
        $this->assertSame($user->id, $authUser['id']);
        $this->assertContains('admin', $authUser['roles']->all());
        $this->assertContains('view-users', $authUser['permissions']->all());

        $this->assertSame(1, $shared['notifications']['count']());
        $items = $shared['notifications']['items']();
        $this->assertCount(1, $items);
        $this->assertSame('Liked', $items[0]['title']);
        $this->assertSame('SAMPLE-001', $items[0]['jav']['code']);
    }
}
