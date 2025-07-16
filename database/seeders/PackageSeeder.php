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
                ['name' => $cat['name']],
                ['description' => $cat['description']]
            );
            $categoryMap[$cat['name']] = $category;
        }

        $packages = [
            [
                'name'               => 'Basic',
                'employee_limit'     => 5,
                'annual_price'       => 4990.00,
                'three_years_price'  => 48990.00,
                'category'           => 'Retail',
            ],
            [
                'name'               => 'Standard',
                'employee_limit'     => 15,
                'annual_price'       => 9990.00,
                'three_years_price'  => 93990.00,
                'category'           => 'Services',
            ],
            [
                'name'               => 'Premium',
                'employee_limit'     => 50,
                'annual_price'       => 19990.00,
                'three_years_price'  => 139990.00,
                'category'           => 'Manufacturing',
            ],
        ];

        foreach ($packages as $pkg) {
            // Create or update the package
            $package = Package::updateOrCreate(
                ['name' => $pkg['name']],
                [
                    'employee_limit'    => $pkg['employee_limit'],
                    'annual_price'      => $pkg['annual_price'],
                    'three_years_price' => $pkg['three_years_price'],
                    'package_type'      => 'general',
                    'user_id'           => null,
                ]
            );

            // Attach business category
            $package->businessCategories()->sync([$categoryMap[$pkg['category']]->id]);
        }
    }
}

