<?php

namespace Tests\Feature\Controllers\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role with permissions
        $this->adminRole = Role::factory()->create(['slug' => 'admin']);
        $viewPermission = Permission::factory()->create(['slug' => 'view-users']);
        $createPermission = Permission::factory()->create(['slug' => 'create-users']);
        $editPermission = Permission::factory()->create(['slug' => 'edit-users']);
        $deletePermission = Permission::factory()->create(['slug' => 'delete-users']);
        $assignPermission = Permission::factory()->create(['slug' => 'assign-roles']);

        $this->adminRole->permissions()->attach([
            $viewPermission->id,
            $createPermission->id,
            $editPermission->id,
            $deletePermission->id,
            $assignPermission->id,
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->adminRole);
    }

    public function test_admin_can_view_users_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.users.index'));

        $this->assertInertiaComponent($response, 'Admin/Users/Index');
    }

    public function test_non_admin_cannot_view_users_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_users_index_can_search_users(): void
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $response = $this->actingAs($this->adminUser)->get(route('admin.users.index', ['search' => 'John']));

        $response->assertOk();
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');
    }

    public function test_users_index_can_filter_by_role(): void
    {
        $moderatorRole = Role::factory()->create(['slug' => 'moderator']);
        $moderator = User::factory()->create();
        $moderator->roles()->attach($moderatorRole);

        $regularUser = User::factory()->create();

        $response = $this->actingAs($this->adminUser)->get(route('admin.users.index', ['role' => 'moderator']));

        $response->assertOk();
        $response->assertSee($moderator->name);
        $response->assertDontSee($regularUser->name);
    }

    public function test_admin_can_create_user(): void
    {
        $roleForNewUser = Role::factory()->create();

        $response = $this->actingAs($this->adminUser)->post(route('admin.users.store'), [
            'name' => 'New User',
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'roles' => [$roleForNewUser->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'username' => 'newuser',
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->adminUser)->put(route('admin.users.update', $user), [
            'name' => 'Updated Name',
            'username' => $user->username,
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->adminUser)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->adminUser)->delete(route('admin.users.destroy', $this->adminUser));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);
    }

    public function test_admin_can_assign_roles_to_user(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $response = $this->actingAs($this->adminUser)->post(route('admin.users.assign-roles', $user), [
            'roles' => [$role->id],
        ]);

        $response->assertRedirect();
        $this->assertTrue($user->fresh()->roles->contains($role));
    }

    public function test_create_user_validates_required_fields(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.users.store'), []);

        $response->assertSessionHasErrors(['name', 'username', 'email', 'password']);
    }

    public function test_create_user_validates_unique_email(): void
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($this->adminUser)->post(route('admin.users.store'), [
            'name' => 'New User',
            'username' => 'newuser',
            'email' => 'existing@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    private function assertInertiaComponent(TestResponse $response, string $component): void
    {
        $response->assertOk();
        $response->assertViewHas('page');

        $page = $response->viewData('page');
        if (is_string($page)) {
            $page = json_decode($page, true, 512, JSON_THROW_ON_ERROR);
        }

        $this->assertSame($component, $page['component'] ?? null);
    }
}
