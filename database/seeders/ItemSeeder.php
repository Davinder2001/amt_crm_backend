<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Category;

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
            $categoryMap = [];

            // Create categories and subcategories
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
                if (!isset($categoryMap[$subCategory])) continue;

                foreach ($productNames as $productName) {
                    if ($itemIndex > 25) break;

                    $item = Item::create([
                        'company_id'         => $company['id'],
                        'item_code'          => 'ITEM-' . $itemIndex,
                        'name'               => $productName,
                        'measurement'        => null,
                        'featured_image'     => null,
                        'availability_stock' => rand(10, 100),
                        'images'             => [],
                        'catalog'            => (bool)rand(0, 1),
                        'online_visibility'  => true,
                    ]);

                    // Attach main + one optional random category
                    $attachIds = [$categoryMap[$subCategory]];
                    $otherCats = array_diff_key($categoryMap, [$subCategory => true]);
                    if (!empty($otherCats) && rand(0, 1)) {
                        $randomOther = array_rand($otherCats);
                        $attachIds[] = $categoryMap[$randomOther];
                    }

                    $item->categories()->attach($attachIds);
                    $itemIndex++;
                }

                if ($itemIndex > 25) break;
            }
        }
    }
}
