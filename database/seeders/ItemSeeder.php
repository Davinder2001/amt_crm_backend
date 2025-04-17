<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
        ];

        foreach ($companies as $company) {
            // 1. Create Vendors
            $vendorNames = [];
            for ($v = 1; $v <= 3; $v++) {
                $vendor = StoreVendor::create([
                    'company_id'  => $company['id'],
                    'vendor_name' => "Vendor {$v} of {$company['company_name']}",
                ]);
                $vendorNames[] = $vendor->vendor_name;
            }

            // 2. Create Categories & Nested Categories
            $allCategories = collect();
            for ($c = 1; $c <= 3; $c++) {
                $parent = Category::create([
                    'company_id' => $company['id'],
                    'name'       => "Category {$c} for {$company['company_name']}",
                    'parent_id'  => null,
                ]);
                $allCategories->push($parent);

                // two children for each parent
                for ($n = 1; $n <= 2; $n++) {
                    $child = Category::create([
                        'company_id' => $company['id'],
                        'name'       => "Category {$c}.{$n} for {$company['company_name']}",
                        'parent_id'  => $parent->id,
                    ]);
                    $allCategories->push($child);
                }
            }

            // 3. Seed Items and attach to random categories
            for ($i = 1; $i <= 10; $i++) {
                $item = Item::create([
                    'company_id'          => $company['id'],
                    'item_code'           => $i,
                    'name'                => "Item {$i} for {$company['company_name']}",
                    'quantity_count'      => rand(50, 500),
                    'measurement'         => 'kg',
                    'purchase_date'       => now()->subDays(rand(1, 30)),
                    'date_of_manufacture' => now()->subMonths(1),
                    'date_of_expiry'      => now()->addMonths(6),
                    'brand_name'          => 'Brand ' . chr(64 + $i),
                    'replacement'         => 'Replace after 6 months',
                    'vendor_name'         => $vendorNames[array_rand($vendorNames)],
                    'availability_stock'  => rand(10, 100),
                    'cost_price'          => rand(100, 500),
                    'selling_price'       => rand(600, 900),
                    'images'              => json_encode([]),
                    'catalog'             => false,
                    'online_visibility'   => true,
                ]);

                // attach 1–2 random categories
                $randomCats = $allCategories
                    ->random(rand(1, 2))
                    ->pluck('id')
                    ->toArray();

                $item->categories()->attach($randomCats);
            }
        }
    }
}
