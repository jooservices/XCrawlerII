<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_has_roles_relationship(): void
    {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();
        $permission->roles()->attach($role);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $permission->roles);
        $this->assertTrue($permission->roles->contains($role));
    }

    public function test_permission_can_be_created_with_required_fields(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'Test Permission',
            'slug' => 'test-permission',
            'description' => 'This is a test permission',
        ]);

        $this->assertEquals('Test Permission', $permission->name);
        $this->assertEquals('test-permission', $permission->slug);
        $this->assertEquals('This is a test permission', $permission->description);
    }

    public function test_permission_slug_is_unique(): void
    {
        Permission::factory()->create(['slug' => 'unique-permission']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Permission::factory()->create(['slug' => 'unique-permission']);
    }
}
