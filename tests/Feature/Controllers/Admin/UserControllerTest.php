<?php

namespace Tests\Feature\Controllers\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Role $adminRole;

    private function asAuthenticatable(User $user): Authenticatable
    {
        assert($user instanceof Authenticatable);

        return $user;
    }

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
        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->get(route('admin.users.index'));

        $this->assertInertiaComponent($response, 'Admin/Users/Index');
    }

    public function test_non_admin_cannot_view_users_index(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($this->asAuthenticatable($user))->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_users_index_can_search_users(): void
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->get(route('admin.users.index', ['search' => 'John']));

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

        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->get(route('admin.users.index', ['role' => 'moderator']));

        $response->assertOk();
        $response->assertSee($moderator->name);
        $response->assertDontSee($regularUser->name);
    }

    public function test_admin_can_create_user(): void
    {
        $roleForNewUser = Role::factory()->create();

        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->post(route('admin.users.store'), [
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

    public function test_admin_can_view_create_user_form(): void
    {
        $role = Role::factory()->create(['name' => 'Moderator']);

        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->get(route('admin.users.create'));

        $this->assertInertiaComponent($response, 'Admin/Users/Create');
        $response->assertSee($role->name);
    }

    public function test_admin_can_view_user_details_page(): void
    {
        $user = User::factory()->create(['name' => 'Detail User']);
        $role = Role::factory()->create(['slug' => 'moderator']);
        $permission = Permission::factory()->create(['slug' => 'view-dashboard']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->get(route('admin.users.show', $user));

        $this->assertInertiaComponent($response, 'Admin/Users/Show');
        $response->assertSee('Detail User');
    }

    public function test_admin_can_view_edit_user_form(): void
    {
        $user = User::factory()->create(['name' => 'Editable User']);
        $role = Role::factory()->create(['slug' => 'editor']);
        $user->roles()->attach($role);

        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->get(route('admin.users.edit', $user));

        $this->assertInertiaComponent($response, 'Admin/Users/Edit');
        $response->assertSee('Editable User');
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->put(route('admin.users.update', $user), [
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

    public function test_admin_can_update_user_password_when_provided(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password-123'),
        ]);

        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertTrue(Hash::check('new-password-456', $user->fresh()->password));
    }

    public function test_admin_can_update_user_and_sync_roles_when_roles_are_provided(): void
    {
        $user = User::factory()->create();
        $roleA = Role::factory()->create(['slug' => 'role-a']);
        $roleB = Role::factory()->create(['slug' => 'role-b']);
        $user->roles()->attach($roleA->id);

        $response = $this->actingAs($this->adminUser)->put(route('admin.users.update', $user), [
            'name' => 'Synced Roles User',
            'username' => $user->username,
            'email' => $user->email,
            'roles' => [$roleB->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $fresh = $user->fresh('roles');
        $this->assertSame('Synced Roles User', $fresh->name);
        $this->assertTrue($fresh->roles->contains($roleB));
        $this->assertFalse($fresh->roles->contains($roleA));
    }

    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->delete(route('admin.users.destroy', $this->adminUser));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);
    }

    public function test_admin_can_assign_roles_to_user(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->post(route('admin.users.assign-roles', $user), [
            'roles' => [$role->id],
        ]);

        $response->assertRedirect();
        $this->assertTrue($user->fresh()->roles->contains($role));
    }

    public function test_create_user_validates_required_fields(): void
    {
        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->post(route('admin.users.store'), []);

        $response->assertSessionHasErrors(['name', 'username', 'email', 'password']);
    }

    public function test_create_user_validates_unique_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($this->asAuthenticatable($this->adminUser))->post(route('admin.users.store'), [
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
