<?php

namespace Tests\Feature\Auth;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_routes(): void
    {
        $adminRole = Role::factory()->create(['slug' => 'admin']);
        $viewPermission = Permission::factory()->create(['slug' => 'view-users']);
        $adminRole->permissions()->attach($viewPermission);

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
    }

    public function test_moderator_can_access_allowed_routes(): void
    {
        $moderatorRole = Role::factory()->create(['slug' => 'moderator']);
        $viewPermission = Permission::factory()->create(['slug' => 'view-users']);
        $moderatorRole->permissions()->attach($viewPermission);

        $moderator = User::factory()->create();
        $moderator->roles()->attach($moderatorRole);

        $response = $this->actingAs($moderator)->get(route('admin.users.index'));

        $response->assertOk();
    }

    public function test_regular_user_cannot_access_admin_routes(): void
    {
        $userRole = Role::factory()->create(['slug' => 'user']);
        $user = User::factory()->create();
        $user->roles()->attach($userRole);

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_user_without_role_cannot_access_protected_routes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_admin_routes(): void
    {
        $response = $this->get(route('admin.users.index'));

        $response->assertStatus(302); // Redirect to login
    }

    public function test_user_with_permission_can_perform_action(): void
    {
        $role = Role::factory()->create(['slug' => 'admin']); // User needs admin role for route access
        $createPermission = Permission::factory()->create(['slug' => 'create-users']);
        $role->permissions()->attach($createPermission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $newUserRole = Role::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.users.store'), [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'roles' => [$newUserRole->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_without_permission_cannot_perform_action(): void
    {
        $role = Role::factory()->create(['slug' => 'admin']); // User has admin role but not create-users permission
        $viewPermission = Permission::factory()->create(['slug' => 'view-users']);
        $role->permissions()->attach($viewPermission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->post(route('admin.users.store'), [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertForbidden();
    }

    public function test_user_with_multiple_roles_inherits_all_permissions(): void
    {
        $role1 = Role::factory()->create();
        $permission1 = Permission::factory()->create(['slug' => 'view-users']);
        $role1->permissions()->attach($permission1);

        $role2 = Role::factory()->create();
        $permission2 = Permission::factory()->create(['slug' => 'create-users']);
        $role2->permissions()->attach($permission2);

        $user = User::factory()->create();
        $user->roles()->attach([$role1->id, $role2->id]);

        $this->assertTrue($user->hasPermission('view-users'));
        $this->assertTrue($user->hasPermission('create-users'));
    }
}
