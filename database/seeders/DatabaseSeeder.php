<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            PermissionsTableSeeder::class,
            SuperAdminSeeder::class,
            PackageSeeder::class,
            AdminSeeder::class,
            // RolesTableSeeder::class,
            EmployeeSeeder::class,
            ItemSeeder::class,
        ]);
    }
}
