<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;
use App\Models\BusinessCategory;

class PackageSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Retail', 'description' => 'Retail businesses'],
            ['name' => 'Services', 'description' => 'Service providers'],
            ['name' => 'Manufacturing', 'description' => 'Manufacturing units'],
        ];

        $categoryMap = [];

        foreach ($categories as $cat) {
            $category = BusinessCategory::updateOrCreate(
                ['name'         => $cat['name']],
                ['description'  => $cat['description']]
            );
            $categoryMap[$cat['name']] = $category;
        }

        $packages = [
            [
                'name'               => 'Basic',
                'employee_numbers'   => 5,
                'items_number'       => 50,
                'daily_tasks_number' => 20,
                'invoices_number'    => 100,
                'monthly_price'      => 499.00,
                'annual_price'       => 4990.00,
                'category'           => 'Retail',
            ],
            [
                'name'              => 'Standard',
                'employee_numbers'  => 15,
                'items_number'      => 200,
                'daily_tasks_number' => 50,
                'invoices_number' => 300,
                'monthly_price' => 999.00,
                'annual_price' => 9990.00,
                'category' => 'Services',
            ],
            [
                'name' => 'Premium',
                'employee_numbers' => 50,
                'items_number' => 1000,
                'daily_tasks_number' => 200,
                'invoices_number' => 1000,
                'monthly_price' => 1999.00,
                'annual_price' => 19990.00,
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
                    'monthly_price'         => $pkg['monthly_price'],
                    'annual_price'          => $pkg['annual_price'],
                ]
            );

            $package->businessCategories()->sync([$categoryMap[$pkg['category']]->id]);
        }
    }
}
