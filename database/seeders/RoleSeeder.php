<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions (Extendable list)
        $permissions = [
            'manage users',
            'manage stories',
            'create stories',
            'read stories',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and Assign Permissions

        // 1. Admin: Everything
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // 2. Parent: Can manage their own data and create stories
        $parentRole = Role::firstOrCreate(['name' => 'parent']);
        $parentRole->givePermissionTo(['create stories', 'read stories']);

        // 3. Kid: Can read stories
        $kidRole = Role::firstOrCreate(['name' => 'kid']);
        $kidRole->givePermissionTo(['read stories']);

        // Create Default Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@lmf.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole($adminRole);

        $this->command->info('Roles, Permissions and Admin User created successfully.');
    }
}
