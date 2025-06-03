<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Category;
use App\Models\StoreVendor;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            ['id' => 2, 'company_name' => 'Demo Admin Company One'],
            ['id' => 3, 'company_name' => 'Demo Admin Company Two'],
            ['id' => 4, 'company_name' => 'Demo Admin Company Three'],
            ['id' => 5, 'company_name' => 'Demo Admin Company Four'],
            ['id' => 6, 'company_name' => 'Demo Admin Company Five'],
        ];

        $baseCategories = [
            'Electronics'     => ['Mobiles', 'Laptops', 'Cameras'],
            'Clothing'        => ['Men', 'Women', 'Kids'],
            'Home Appliances' => ['Refrigerators', 'Washing Machines', 'Microwaves'],
        ];

        $sampleProducts = [
            'Mobiles'          => ['iPhone 13', 'Samsung Galaxy S21', 'Redmi Note 12'],
            'Laptops'          => ['Dell XPS 13', 'MacBook Air', 'HP Pavilion'],
            'Cameras'          => ['Canon EOS 90D', 'Nikon Z6', 'Sony Alpha A7'],
            'Men'              => ['Men T-Shirt', 'Men Jeans', 'Men Jacket'],
            'Women'            => ['Women Kurti', 'Women Jeans', 'Women Top'],
            'Kids'             => ['Kids Shirt', 'Kids Shoes', 'Kids Toys'],
            'Refrigerators'    => ['LG Double Door', 'Samsung Smart Fridge', 'Whirlpool Single Door'],
            'Washing Machines' => ['IFB Front Load', 'Bosch Top Load', 'Samsung Washer'],
            'Microwaves'       => ['IFB Microwave', 'LG Grill Microwave', 'Samsung Convection Oven'],
        ];

        foreach ($companies as $company) {

            $vendorNames = [];
            for ($v = 1; $v <= 3; $v++) {
                $vendor = StoreVendor::create([
                    'company_id'  => $company['id'],
                    'vendor_name' => "Vendor {$v} of {$company['company_name']}",
                ]);
                $vendorNames[] = $vendor->vendor_name;
            }

            $categoryMap = [];
            foreach ($baseCategories as $parent => $subs) {
                $parentCat = Category::create([
                    'company_id' => $company['id'],
                    'name'       => $parent,
                    'parent_id'  => null,
                ]);
                foreach ($subs as $sub) {
                    $childCat = Category::create([
                        'company_id' => $company['id'],
                        'name'       => $sub,
                        'parent_id'  => $parentCat->id,
                    ]);
                    $categoryMap[$sub] = $childCat->id;
                }
            }

            $itemIndex = 1;
            foreach ($sampleProducts as $subCategory => $productNames) {
                if (!isset($categoryMap[$subCategory])) {
                    continue;
                }

                foreach ($productNames as $productName) {
                    if ($itemIndex > 25) break;

                    $item = Item::create([
                        'company_id'          => $company['id'],
                        'item_code'           => $itemIndex,
                        'name'                => $productName,
                        'quantity_count'      => rand(50, 200),
                        'measurement'         => 'pcs',
                        'purchase_date'       => now()->subDays(rand(1, 30)),
                        'date_of_manufacture' => now()->subMonths(1),
                        'date_of_expiry'      => now()->addMonths(12),
                        'brand_name'          => 'Brand ' . chr(64 + rand(1, 26)),
                        'replacement'         => 'Replace after 1 year',
                        'vendor_name'         => $vendorNames[array_rand($vendorNames)],
                        'availability_stock'  => rand(10, 100),
                        'cost_price'          => rand(100, 800),
                        'selling_price'       => rand(900, 1500),
                        'images'              => json_encode([]),
                        'catalog'             => false,
                        'online_visibility'   => true,
                    ]);

                    $attachIds = [$categoryMap[$subCategory]];

                    $otherCategories = array_diff_key($categoryMap, [$subCategory => true]);
                    if (!empty($otherCategories) && rand(0, 1)) {
                        $randomOtherKey = array_rand($otherCategories);
                        $attachIds[] = $categoryMap[$randomOtherKey];
                    }

                    $item->categories()->attach($attachIds);
                    $itemIndex++;
                }

                if ($itemIndex > 25) break;
            }
        }
    }
}
