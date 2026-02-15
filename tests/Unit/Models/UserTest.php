<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Rating;
use Modules\JAV\Models\UserJavHistory;
use Modules\JAV\Models\UserLikeNotification;
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

    public function test_user_avatar_url_uses_stored_avatar_when_present(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'avatar_path' => 'avatars/test.png',
        ]);

        $this->assertSame('/storage/avatars/test.png', $user->avatar_url);
    }

    public function test_user_avatar_url_falls_back_to_gravatar_when_no_avatar_is_set(): void
    {
        $user = User::factory()->create([
            'email' => 'avatar@example.com',
            'avatar_path' => null,
        ]);

        $hash = md5('avatar@example.com');
        $this->assertSame("https://www.gravatar.com/avatar/{$hash}?d=mp&s=80", $user->avatar_url);
    }

    public function test_user_has_favorites_notifications_and_history_relationships(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $favorite = Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_id' => $jav->id,
            'favoritable_type' => Jav::class,
        ]);

        $notification = UserLikeNotification::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'title' => 'Liked',
        ]);

        $history = UserJavHistory::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'action' => 'view',
        ]);

        $this->assertTrue($user->favorites->contains($favorite));
        $this->assertTrue($user->javNotifications->contains($notification));
        $this->assertTrue($user->javHistory->contains($history));
    }

    public function test_get_all_permissions_returns_unique_permissions_from_all_roles(): void
    {
        $user = User::factory()->create();
        $roleA = Role::factory()->create(['slug' => 'role-a']);
        $roleB = Role::factory()->create(['slug' => 'role-b']);
        $sharedPermission = Permission::factory()->create(['slug' => 'shared-permission']);
        $extraPermission = Permission::factory()->create(['slug' => 'extra-permission']);

        $roleA->permissions()->attach([$sharedPermission->id]);
        $roleB->permissions()->attach([$sharedPermission->id, $extraPermission->id]);
        $user->roles()->attach([$roleA->id, $roleB->id]);

        $permissions = $user->fresh('roles.permissions')->getAllPermissions();

        $this->assertCount(2, $permissions);
        $this->assertTrue($permissions->contains('slug', 'shared-permission'));
        $this->assertTrue($permissions->contains('slug', 'extra-permission'));
    }
}
