<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            SuperAdminSeeder::class,
            AdminSeeder::class,
            PermissionsTableSeeder::class,
            RolesTableSeeder::class,
        ]);
    }
}
