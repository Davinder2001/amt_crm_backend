<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;
use App\Models\BusinessCategory;

class PackageSeeder extends Seeder
{
    public function run()
    {
        // Step 1: Create 3 business categories
        $categories = [
            ['name' => 'Retail', 'description' => 'Retail businesses'],
            ['name' => 'Services', 'description' => 'Service providers'],
            ['name' => 'Manufacturing', 'description' => 'Manufacturing units'],
        ];

        $categoryMap = [];

        foreach ($categories as $cat) {
            $category = BusinessCategory::updateOrCreate(
                ['name' => $cat['name']],
                ['description' => $cat['description']]
            );
            $categoryMap[$cat['name']] = $category;
        }

        $packages = [
            [
                'name' => 'Basic',
                'employee_numbers' => 5,
                'items_number' => 50,
                'daily_tasks_number' => 20,
                'invoices_number' => 100,
                'package_type' => 'monthly',
                'price' => 499.00,
                'category' => 'Retail',
            ],
            [
                'name' => 'Standard',
                'employee_numbers' => 15,
                'items_number' => 200,
                'daily_tasks_number' => 50,
                'invoices_number' => 300,
                'package_type' => 'monthly',
                'price' => 999.00,
                'category' => 'Services',
            ],
            [
                'name' => 'Premium',
                'employee_numbers' => 50,
                'items_number' => 1000,
                'daily_tasks_number' => 200,
                'invoices_number' => 1000,
                'package_type' => 'monthly',
                'price' => 1999.00,
                'category' => 'Manufacturing',
            ],
        ];

        foreach ($packages as $pkg) {
            $package = Package::updateOrCreate(
                ['name' => $pkg['name']],
                [
                    'employee_numbers'      => $pkg['employee_numbers'],
                    'items_number'          => $pkg['items_number'],
                    'daily_tasks_number'    => $pkg['daily_tasks_number'],
                    'invoices_number'       => $pkg['invoices_number'],
                    'package_type'          => $pkg['package_type'],
                    'price'                 => $pkg['price'],
                ]
            );

            $package->businessCategories()->sync([$categoryMap[$pkg['category']]->id]);
        }
    }
}
