<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // User Management
            ['name' => 'View Users', 'slug' => 'view-users', 'description' => 'Can view user list'],
            ['name' => 'Create Users', 'slug' => 'create-users', 'description' => 'Can create new users'],
            ['name' => 'Edit Users', 'slug' => 'edit-users', 'description' => 'Can edit user information'],
            ['name' => 'Delete Users', 'slug' => 'delete-users', 'description' => 'Can delete users'],

            // Role Management
            ['name' => 'View Roles', 'slug' => 'view-roles', 'description' => 'Can view role list'],
            ['name' => 'Create Roles', 'slug' => 'create-roles', 'description' => 'Can create new roles'],
            ['name' => 'Edit Roles', 'slug' => 'edit-roles', 'description' => 'Can edit role information'],
            ['name' => 'Delete Roles', 'slug' => 'delete-roles', 'description' => 'Can delete roles'],
            ['name' => 'Assign Roles', 'slug' => 'assign-roles', 'description' => 'Can assign roles to users'],

            // Content Management
            ['name' => 'Manage Content', 'slug' => 'manage-content', 'description' => 'Can manage JAV content'],
            ['name' => 'Delete Content', 'slug' => 'delete-content', 'description' => 'Can delete JAV content'],
            ['name' => 'Approve Content', 'slug' => 'approve-content', 'description' => 'Can approve user submissions'],

            // Statistics & Reports
            ['name' => 'View Statistics', 'slug' => 'view-statistics', 'description' => 'Can view admin statistics'],
            ['name' => 'View Reports', 'slug' => 'view-reports', 'description' => 'Can view system reports'],

            // Bulk Operations
            ['name' => 'Bulk Operations', 'slug' => 'bulk-operations', 'description' => 'Can perform bulk operations'],

            // Moderation
            ['name' => 'Moderate Comments', 'slug' => 'moderate-comments', 'description' => 'Can moderate user comments'],
            ['name' => 'Moderate Reviews', 'slug' => 'moderate-reviews', 'description' => 'Can moderate user reviews'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Create Roles
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'description' => 'Full system access with all permissions',
            ]
        );

        $moderatorRole = Role::firstOrCreate(
            ['slug' => 'moderator'],
            [
                'name' => 'Moderator',
                'description' => 'Can moderate content and user interactions',
            ]
        );

        $userRole = Role::firstOrCreate(
            ['slug' => 'user'],
            [
                'name' => 'User',
                'description' => 'Regular user with basic permissions',
            ]
        );

        // Assign all permissions to admin
        $adminRole->permissions()->sync(Permission::all()->pluck('id'));

        // Assign specific permissions to moderator
        $moderatorPermissions = Permission::whereIn('slug', [
            'view-users',
            'manage-content',
            'moderate-comments',
            'moderate-reviews',
            'view-statistics',
        ])->pluck('id');
        $moderatorRole->permissions()->sync($moderatorPermissions);

        // No permissions for regular users (they have basic app access)
        $userRole->permissions()->sync([]);

        // Create admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@xcrawler.local'],
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role to admin user
        $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Admin user created: admin@xcrawler.local / password');
    }
}
