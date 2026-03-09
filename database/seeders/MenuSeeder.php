<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;
use Spatie\Permission\Models\Role;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles exist (just in case, though likely seeded elsewhere)
        // $roleAdult = Role::firstOrCreate(['name' => 'adult']);

        // 1. My Castle (Home)
        MenuItem::firstOrCreate(
            ['route' => 'home'],
            [
                'label' => 'sidebar.my_castle',
                'icon' => '🏰',
                'order' => 1
            ]
        );

        // 2. Search Stories
        MenuItem::firstOrCreate(
            ['route' => 'search'],
            [
                'label' => 'sidebar.search_stories',
                'icon' => '🔍',
                'order' => 2
            ]
        );

        // 3. Create AI Stories (Restricted)
        $ai = MenuItem::firstOrCreate(
            ['route' => 'ai-discovery'],
            [
                'label' => 'sidebar.create_ai_stories',
                'icon' => '🧞',
                'order' => 3
            ]
        );
        // Assign role if needed (Assuming many-to-many relationship set up in model)
        // $ai->roles()->syncWithoutDetaching([$roleAdult->id]);

        // 4. My Lab (Restricted)
        $lab = MenuItem::firstOrCreate(
            ['route' => 'laboratorio'],
            [
                'label' => 'sidebar.my_lab',
                'icon' => '✍️',
                'order' => 4
            ]
        );
        // $lab->roles()->syncWithoutDetaching([$roleAdult->id]);

        // 5. Magic Shop (Restricted)
        $shop = MenuItem::firstOrCreate(
            ['route' => 'gadgets'],
            [
                'label' => 'sidebar.magic_shop',
                'icon' => '🎁',
                'order' => 5
            ]
        );
        // $shop->roles()->syncWithoutDetaching([$roleAdult->id]);
    }
}
