<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_has_users_relationship(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();
        $role->users()->attach($user);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $role->users);
        $this->assertTrue($role->users->contains($user));
    }

    public function test_role_has_permissions_relationship(): void
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $role->permissions()->attach($permission);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $role->permissions);
        $this->assertTrue($role->permissions->contains($permission));
    }

    public function test_role_can_check_if_has_permission(): void
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create(['slug' => 'test-permission']);
        $role->permissions()->attach($permission);

        $this->assertTrue($role->hasPermission('test-permission'));
        $this->assertFalse($role->hasPermission('non-existent-permission'));
    }

    public function test_role_can_give_permission(): void
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();

        $role->givePermission($permission);

        $this->assertTrue($role->permissions->contains($permission));
    }

    public function test_role_can_revoke_permission(): void
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $role->permissions()->attach($permission);

        $role->revokePermission($permission);

        $this->assertFalse($role->fresh()->permissions->contains($permission));
    }

    public function test_give_permission_does_not_duplicate(): void
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();

        $role->givePermission($permission);
        $role->givePermission($permission); // Try to add again

        $this->assertEquals(1, $role->fresh()->permissions->count());
    }
}
