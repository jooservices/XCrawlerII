<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\PermissionMiddleware;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new PermissionMiddleware();
    }

    public function test_middleware_allows_user_with_required_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create(['slug' => 'edit-users']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'), 'edit-users');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_denies_user_without_required_permission(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Forbidden');

        $user = User::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create(['slug' => 'view-users']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->middleware->handle($request, fn ($req) => response('OK'), 'edit-users');
    }

    public function test_middleware_denies_guest_user(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => null);

        $this->middleware->handle($request, fn ($req) => response('OK'), 'edit-users');
    }

    public function test_user_with_multiple_roles_has_correct_permissions(): void
    {
        $user = User::factory()->create();

        $role1 = Role::factory()->create();
        $permission1 = Permission::factory()->create(['slug' => 'view-users']);
        $role1->permissions()->attach($permission1);

        $role2 = Role::factory()->create();
        $permission2 = Permission::factory()->create(['slug' => 'edit-users']);
        $role2->permissions()->attach($permission2);

        $user->roles()->attach([$role1->id, $role2->id]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'), 'edit-users');

        $this->assertEquals('OK', $response->getContent());
    }
}
