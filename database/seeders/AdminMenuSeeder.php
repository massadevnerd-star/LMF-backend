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
                'label' => 'Admin Dashboard',
                'icon' => 'shield',
                'order' => 100
            ]
        );

        // 3. Assign to Admin Role
        $adminMenu->roles()->syncWithoutDetaching([$adminRole->id]);

        // 4. Create standard menu items for Users (if they don't exist)
        // This ensures the user has something to see immediately
        $userItems = [
            ['label' => 'Bacheca', 'route' => '/dashboard', 'icon' => 'dashboard', 'order' => 1],
            ['label' => 'Le Mie Storie', 'route' => '/library', 'icon' => 'book', 'order' => 2],
        ];

        foreach ($userItems as $item) {
            $menu = MenuItem::firstOrCreate(
                ['route' => $item['route']],
                $item
            );
            // Assign to public or specific roles if needed. 
            // For now, if we don't assign roles, our controller logic (MyMenu) might need to handle "public" items.
            // Let's create a 'user' role for clarity? Or just letting unassigned items be public?
            // Based on my controller logic: whereHas('roles')... orWhereDoesntHave('roles').
            // So these will be visible to everyone.
        }

        $this->command->info('Admin Menu and Role configured.');
    }
}
