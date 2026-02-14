<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Rating;
use Modules\JAV\Models\Watchlist;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_roles_relationship(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $user->roles()->attach($role);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->roles);
        $this->assertTrue($user->roles->contains($role));
    }

    public function test_user_can_check_if_has_role(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['slug' => 'test-role']);
        $user->roles()->attach($role);

        $this->assertTrue($user->hasRole('test-role'));
        $this->assertFalse($user->hasRole('non-existent-role'));
    }

    public function test_user_can_check_if_has_any_role(): void
    {
        $user = User::factory()->create();
        $role1 = Role::factory()->create(['slug' => 'role-1']);
        $role2 = Role::factory()->create(['slug' => 'role-2']);
        $user->roles()->attach($role1);

        $this->assertTrue($user->hasAnyRole(['role-1', 'role-3']));
        $this->assertFalse($user->hasAnyRole(['role-3', 'role-4']));
    }

    public function test_user_can_check_if_has_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create(['slug' => 'test-permission']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('test-permission'));
        $this->assertFalse($user->hasPermission('non-existent-permission'));
    }

    public function test_user_is_admin_check(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::factory()->create(['slug' => 'admin']);
        $user->roles()->attach($adminRole);

        $this->assertTrue($user->isAdmin());
    }

    public function test_user_is_not_admin_without_admin_role(): void
    {
        $user = User::factory()->create();
        $userRole = Role::factory()->create(['slug' => 'user']);
        $user->roles()->attach($userRole);

        $this->assertFalse($user->isAdmin());
    }

    public function test_user_is_moderator_check(): void
    {
        $user = User::factory()->create();
        $moderatorRole = Role::factory()->create(['slug' => 'moderator']);
        $user->roles()->attach($moderatorRole);

        $this->assertTrue($user->isModerator());
    }

    public function test_user_can_assign_role(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $user->assignRole($role);

        $this->assertTrue($user->fresh()->roles->contains($role));
    }

    public function test_user_can_remove_role(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $user->roles()->attach($role);

        $user->removeRole($role);

        $this->assertFalse($user->fresh()->roles->contains($role));
    }

    public function test_assign_role_does_not_duplicate(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $user->assignRole($role);
        $user->assignRole($role); // Try to add again

        $this->assertEquals(1, $user->fresh()->roles->count());
    }

    public function test_user_has_ratings_relationship(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $rating = Rating::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
        ]);

        $this->assertTrue($user->ratings->contains($rating));
    }

    public function test_user_has_watchlists_relationship_aliases(): void
    {
        $user = User::factory()->create();
        $watchlist = Watchlist::factory()->create([
            'user_id' => $user->id,
            'jav_id' => Jav::factory(),
        ]);

        $this->assertTrue($user->watchlist->contains($watchlist));
        $this->assertTrue($user->watchlists->contains($watchlist));
    }
}
