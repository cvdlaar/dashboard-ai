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

        // Content specialisten
        $specialists = [
            ['name' => 'Sophie de Vries',    'email' => 'sophie@logistiekconcurrent.nl'],
            ['name' => 'Tom Bakker',          'email' => 'tom@logistiekconcurrent.nl'],
            ['name' => 'Lisa Janssen',        'email' => 'lisa@logistiekconcurrent.nl'],
            ['name' => 'Daan van den Berg',   'email' => 'daan@logistiekconcurrent.nl'],
            ['name' => 'Emma Willems',        'email' => 'emma@logistiekconcurrent.nl'],
        ];

        foreach ($specialists as $data) {
            $user = \App\Models\User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => bcrypt('changeme123!')]
            );
            $user->syncRoles([$specialistRole]);
        }

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
