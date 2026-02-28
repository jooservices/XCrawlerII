<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature\Http\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Tests\TestCase;

final class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_happy_login_via_email_succeeds(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret-pass'),
        ]);

        $response = $this->post(route('v1.action.auth.login'), [
            'login' => $user->email,
            'password' => 'secret-pass',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_happy_login_via_username_succeeds_when_username_column_exists(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            $this->markTestSkipped('users.username column does not exist in this project state.');
        }

        $user = User::factory()->create([
            'username' => 'john_doe',
            'password' => bcrypt('secret-pass'),
        ]);

        $response = $this->post(route('v1.action.auth.login'), [
            'login' => 'john_doe',
            'password' => 'secret-pass',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_unhappy_login_with_invalid_credentials_returns_validation_error(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->from(route('v1.render.auth.login'))->post(route('v1.action.auth.login'), [
            'login' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('v1.render.auth.login'));
        $response->assertSessionHasErrors('login');
    }

    public function test_happy_logout_invalidates_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('v1.action.auth.logout'));

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_routes_resolve_expected_paths(): void
    {
        $this->assertSame('/auth/login', route('v1.render.auth.login', absolute: false));
        $this->assertSame('/auth/login', route('v1.action.auth.login', absolute: false));
        $this->assertSame('/auth/logout', route('v1.action.auth.logout', absolute: false));
    }
}
