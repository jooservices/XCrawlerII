<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RoleMiddleware;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected RoleMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RoleMiddleware;
    }

    public function test_middleware_allows_user_with_required_role(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['slug' => 'admin']);
        $user->roles()->attach($role);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'), 'admin');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_allows_user_with_any_of_required_roles(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['slug' => 'moderator']);
        $user->roles()->attach($role);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'), 'admin', 'moderator');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_denies_user_without_required_role(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Forbidden');

        $user = User::factory()->create();
        $role = Role::factory()->create(['slug' => 'user']);
        $user->roles()->attach($role);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->middleware->handle($request, fn ($req) => response('OK'), 'admin');
    }

    public function test_middleware_denies_guest_user(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => null);

        $this->middleware->handle($request, fn ($req) => response('OK'), 'admin');
    }
}
