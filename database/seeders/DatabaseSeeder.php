<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            PermissionsTableSeeder::class,
            SuperAdminSeeder::class,
            AdminSeeder::class,
            RolesTableSeeder::class,
        ]);
    }
}
