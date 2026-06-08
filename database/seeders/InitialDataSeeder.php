<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Roles
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $specialistRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'specialist']);

        // Admin user
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@logistiekconcurrent.nl'],
            [
                'name' => 'Admin',
                'password' => bcrypt('changeme123!'),
            ]
        );
        $admin->assignRole($adminRole);

        // Sites
        \App\Models\Site::firstOrCreate(
            ['domain' => 'logistiekconcurrent.nl'],
            [
                'name' => 'Logistiekconcurrent',
                'sitemap_url' => 'https://logistiekconcurrent.nl/sitemap.xml',
                'languages' => ['nl'],
                'is_active' => true,
            ]
        );

        \App\Models\Site::firstOrCreate(
            ['domain' => 'logistiekdirect.be'],
            [
                'name' => 'Logistiek Direct',
                'sitemap_url' => 'https://logistiekdirect.be/sitemap.xml',
                'languages' => ['nl', 'fr'],
                'is_active' => true,
            ]
        );
    }
}
