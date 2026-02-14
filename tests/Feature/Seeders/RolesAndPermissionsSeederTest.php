<?php

namespace Tests\Feature\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesAndPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertDatabaseHas('permissions', ['slug' => 'view-users']);
        $this->assertDatabaseHas('permissions', ['slug' => 'create-users']);
        $this->assertDatabaseHas('permissions', ['slug' => 'edit-users']);
        $this->assertDatabaseHas('permissions', ['slug' => 'delete-users']);
        $this->assertDatabaseHas('permissions', ['slug' => 'view-roles']);
        $this->assertDatabaseHas('permissions', ['slug' => 'manage-content']);
        $this->assertDatabaseHas('permissions', ['slug' => 'moderate-comments']);
    }

    public function test_seeder_creates_roles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertDatabaseHas('roles', ['slug' => 'admin']);
        $this->assertDatabaseHas('roles', ['slug' => 'moderator']);
        $this->assertDatabaseHas('roles', ['slug' => 'user']);
    }

    public function test_seeder_creates_admin_user(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@xcrawler.local',
            'username' => 'admin',
        ]);
    }

    public function test_admin_role_has_all_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $adminRole = Role::where('slug', 'admin')->first();
        $allPermissions = Permission::all();

        $this->assertEquals($allPermissions->count(), $adminRole->permissions->count());
    }

    public function test_moderator_role_has_specific_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $moderatorRole = Role::where('slug', 'moderator')->first();

        $this->assertTrue($moderatorRole->hasPermission('view-users'));
        $this->assertTrue($moderatorRole->hasPermission('manage-content'));
        $this->assertTrue($moderatorRole->hasPermission('moderate-comments'));
        $this->assertFalse($moderatorRole->hasPermission('delete-users'));
        $this->assertFalse($moderatorRole->hasPermission('create-roles'));
    }

    public function test_user_role_has_no_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $userRole = Role::where('slug', 'user')->first();

        $this->assertEquals(0, $userRole->permissions->count());
    }

    public function test_admin_user_has_admin_role(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $adminUser = User::where('email', 'admin@xcrawler.local')->first();
        $adminRole = Role::where('slug', 'admin')->first();

        $this->assertTrue($adminUser->roles->contains($adminRole));
        $this->assertTrue($adminUser->isAdmin());
    }

    public function test_seeder_is_idempotent(): void
    {
        // Run seeder twice
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        // Should still have only 3 roles and 1 admin user
        $this->assertEquals(3, Role::whereIn('slug', ['admin', 'moderator', 'user'])->count());
        $this->assertEquals(1, User::where('email', 'admin@xcrawler.local')->count());
    }
}
