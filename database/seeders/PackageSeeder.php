<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;
use App\Models\PackageLimit;
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
                ['name' => $cat['name']],
                ['description' => $cat['description']]
            );
            $categoryMap[$cat['name']] = $category;
        }

        $packages = [
            [
                'name'               => 'Basic',
                'monthly_limits'     => ['employee_numbers' => 5, 'items_number' => 50, 'daily_tasks_number' => 20, 'invoices_number' => 100],
                'annual_limits'      => ['employee_numbers' => 5, 'items_number' => 50, 'daily_tasks_number' => 20, 'invoices_number' => 100],
                'three_years_limits' => ['employee_numbers' => 5, 'items_number' => 50, 'daily_tasks_number' => 20, 'invoices_number' => 100],
                'monthly_price'      => 499.00,
                'annual_price'       => 4990.00,
                'three_years_price'  => 48990.00,
                'category'           => 'Retail',
            ],
            [
                'name'               => 'Standard',
                'monthly_limits'     => ['employee_numbers' => 15, 'items_number' => 200, 'daily_tasks_number' => 50, 'invoices_number' => 300],
                'annual_limits'      => ['employee_numbers' => 15, 'items_number' => 200, 'daily_tasks_number' => 50, 'invoices_number' => 300],
                'three_years_limits' => ['employee_numbers' => 15, 'items_number' => 200, 'daily_tasks_number' => 50, 'invoices_number' => 300],
                'monthly_price'      => 999.00,
                'annual_price'       => 9990.00,
                'three_years_price'  => 93990.00,
                'category'           => 'Services',
            ],
            [
                'name'               => 'Premium',
                'monthly_limits'     => ['employee_numbers' => 50, 'items_number' => 1000, 'daily_tasks_number' => 200, 'invoices_number' => 1000],
                'annual_limits'      => ['employee_numbers' => 50, 'items_number' => 1000, 'daily_tasks_number' => 200, 'invoices_number' => 1000],
                'three_years_limits' => ['employee_numbers' => 50, 'items_number' => 1000, 'daily_tasks_number' => 200, 'invoices_number' => 1000],
                'monthly_price'      => 1999.00,
                'annual_price'       => 19990.00,
                'three_years_price'  => 139990.00,
                'category'           => 'Manufacturing',
            ],
        ];

        foreach ($packages as $pkg) {
            // Create the package
            $package = Package::updateOrCreate(
                ['name' => $pkg['name']],
                [
                    'monthly_price'      => $pkg['monthly_price'],
                    'annual_price'       => $pkg['annual_price'],
                    'three_years_price'  => $pkg['three_years_price'],
                ]
            );

            // Attach category
            $package->businessCategories()->sync([$categoryMap[$pkg['category']]->id]);

            // Clear existing limits if any
            $package->limits()->delete();

            // Insert limits using model
            foreach (['monthly', 'annual', 'three_years'] as $variant) {
                $limits = $pkg["{$variant}_limits"];
                PackageLimit::create([
                    'package_id'         => $package->id,
                    'variant_type'       => $variant,
                    'employee_numbers'   => $limits['employee_numbers'],
                    'items_number'       => $limits['items_number'],
                    'daily_tasks_number' => $limits['daily_tasks_number'],
                    'invoices_number'    => $limits['invoices_number'],
                ]);
            }
        }
    }
}
