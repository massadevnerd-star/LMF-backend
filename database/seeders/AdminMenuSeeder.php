<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;
use Spatie\Permission\Models\Role;
use App\Models\User;

class AdminMenuSeeder extends Seeder
{
    public function run()
    {
        // 1. Ensure Admin Role Exists
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // 2. Create Admin Menu Item
        $adminMenu = MenuItem::firstOrCreate(
            ['route' => '/admin'],
            [
                'label' => 'Area Admin',
                'icon' => '🛠️',
                'order' => 100
            ]
        );

        // 3. Assign to Admin Role
        $adminMenu->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->command->info('Admin Menu and Role configured.');
    }
}
