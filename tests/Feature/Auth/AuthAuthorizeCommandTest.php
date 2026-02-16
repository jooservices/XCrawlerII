<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAuthorizeCommandTest extends TestCase
{
    use RefreshDatabase;

    private const ROOT_ADMIN_EMAIL = 'admin@xcrawler.local';

    public function test_it_can_change_root_admin_password_without_current_password(): void
    {
        $adminRole = Role::factory()->create(['slug' => 'admin']);
        $rootAdmin = User::factory()->create([
            'email' => self::ROOT_ADMIN_EMAIL,
            'password' => Hash::make('old-password-123'),
        ]);
        $rootAdmin->roles()->attach($adminRole);

        $this->artisan('auth:authorize', [
            '--new-password' => 'new-password-123',
        ])->assertExitCode(0);

        $this->assertTrue(Hash::check('new-password-123', (string) $rootAdmin->fresh()->password));
    }

    public function test_it_can_test_access_with_correct_password(): void
    {
        $adminRole = Role::factory()->create(['slug' => 'admin']);
        $rootAdmin = User::factory()->create([
            'email' => self::ROOT_ADMIN_EMAIL,
            'password' => Hash::make('known-password-123'),
        ]);
        $rootAdmin->roles()->attach($adminRole);

        $this->artisan('auth:authorize', [
            '--test-access' => true,
            '--password' => 'known-password-123',
        ])->assertExitCode(0);
    }

    public function test_it_fails_access_test_with_invalid_password(): void
    {
        $adminRole = Role::factory()->create(['slug' => 'admin']);
        $rootAdmin = User::factory()->create([
            'email' => self::ROOT_ADMIN_EMAIL,
            'password' => Hash::make('known-password-123'),
        ]);
        $rootAdmin->roles()->attach($adminRole);

        $this->artisan('auth:authorize', [
            '--test-access' => true,
            '--password' => 'wrong-password-123',
        ])->assertExitCode(1);
    }
}