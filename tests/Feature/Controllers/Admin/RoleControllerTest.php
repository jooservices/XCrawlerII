<?php

namespace Tests\Feature\Controllers\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role with permissions
        $this->adminRole = Role::factory()->create(['slug' => 'admin']);
        $viewPermission = Permission::factory()->create(['slug' => 'view-roles']);
        $createPermission = Permission::factory()->create(['slug' => 'create-roles']);
        $editPermission = Permission::factory()->create(['slug' => 'edit-roles']);
        $deletePermission = Permission::factory()->create(['slug' => 'delete-roles']);

        $this->adminRole->permissions()->attach([
            $viewPermission->id,
            $createPermission->id,
            $editPermission->id,
            $deletePermission->id,
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->adminRole);
    }

    public function test_admin_can_view_roles_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.roles.index'));

        $response->assertOk();
        $response->assertViewIs('admin.roles.index');
    }

    public function test_non_admin_cannot_view_roles_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.roles.index'));

        $response->assertForbidden();
    }

    public function test_roles_index_can_search_roles(): void
    {
        Role::factory()->create(['name' => 'Editor']);
        Role::factory()->create(['name' => 'Viewer']);

        $response = $this->actingAs($this->adminUser)->get(route('admin.roles.index', ['search' => 'Editor']));

        $response->assertOk();
        $response->assertSee('Editor');
        $response->assertDontSee('Viewer');
    }

    public function test_admin_can_create_role(): void
    {
        $permission = Permission::factory()->create();

        $response = $this->actingAs($this->adminUser)->post(route('admin.roles.store'), [
            'name' => 'New Role',
            'slug' => 'new-role',
            'description' => 'A new role for testing',
            'permissions' => [$permission->id],
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseHas('roles', [
            'slug' => 'new-role',
        ]);
    }

    public function test_admin_can_update_role(): void
    {
        $role = Role::factory()->create();

        $response = $this->actingAs($this->adminUser)->put(route('admin.roles.update', $role), [
            'name' => 'Updated Role',
            'slug' => $role->slug,
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Updated Role',
        ]);
    }

    public function test_admin_can_delete_custom_role(): void
    {
        $role = Role::factory()->create(['slug' => 'custom-role']);

        $response = $this->actingAs($this->adminUser)->delete(route('admin.roles.destroy', $role));

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_admin_cannot_delete_core_roles(): void
    {
        // Use the existing admin role from setUp
        $response = $this->actingAs($this->adminUser)->delete(route('admin.roles.destroy', $this->adminRole));

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $this->adminRole->id]);
    }

    public function test_admin_can_view_role_details(): void
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $role->permissions()->attach($permission);

        $response = $this->actingAs($this->adminUser)->get(route('admin.roles.show', $role));

        $response->assertOk();
        $response->assertViewIs('admin.roles.show');
        $response->assertSee($role->name);
    }

    public function test_create_role_validates_required_fields(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.roles.store'), []);

        $response->assertSessionHasErrors(['name', 'slug']);
    }

    public function test_create_role_validates_unique_slug(): void
    {
        $existingRole = Role::factory()->create(['slug' => 'existing-role']);

        $response = $this->actingAs($this->adminUser)->post(route('admin.roles.store'), [
            'name' => 'New Role',
            'slug' => 'existing-role',
        ]);

        $response->assertSessionHasErrors(['slug']);
    }

    public function test_role_slug_is_auto_generated_from_name(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.roles.store'), [
            'name' => 'Test Role Name',
        ]);

        $this->assertDatabaseHas('roles', [
            'slug' => 'test-role-name',
        ]);
    }

    public function test_admin_can_update_role_permissions(): void
    {
        $role = Role::factory()->create();
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();

        $response = $this->actingAs($this->adminUser)->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'slug' => $role->slug,
            'permissions' => [$permission1->id, $permission2->id],
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertTrue($role->fresh()->permissions->contains($permission1));
        $this->assertTrue($role->fresh()->permissions->contains($permission2));
    }
}
