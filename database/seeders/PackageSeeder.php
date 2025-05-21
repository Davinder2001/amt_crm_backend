<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;

class PackageSeeder extends Seeder
{
    public function run()
    {
        $packages = [
            [
                'name' => 'Basic',
                'employee_numbers' => 5,
                'items_number' => 50,
                'daily_tasks_number' => 20,
                'invoices_number' => 100,
                'package_type' => 'monthly',
                'price' => 499.00,
            ],
            [
                'name' => 'Standard',
                'employee_numbers' => 15,
                'items_number' => 200,
                'daily_tasks_number' => 50,
                'invoices_number' => 300,
                'package_type' => 'monthly',
                'price' => 999.00,
            ],
            [
                'name' => 'Premium',
                'employee_numbers' => 50,
                'items_number' => 1000,
                'daily_tasks_number' => 200,
                'invoices_number' => 1000,
                'package_type' => 'monthly',
                'price' => 1999.00,
            ],
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(['name' => $package['name']], $package);
        }
    }
}
