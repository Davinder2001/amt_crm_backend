<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\StoreVendor;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            ['id' => 2, 'company_id' => 'AMTCOM0000002', 'company_name' => 'Demo Admin Company One', 'company_slug' => 'demo-admin-company-one'],
            ['id' => 3, 'company_id' => 'AMTCOM0000003', 'company_name' => 'Demo Admin Company Two', 'company_slug' => 'demo-admin-company-two'],
            ['id' => 4, 'company_id' => 'AMTCOM0000004', 'company_name' => 'Demo Admin Company Three', 'company_slug' => 'demo-admin-company-three'],
        ];

        foreach ($companies as $company) {
            $vendorIds = [];

            // Create 3 vendors per company
            for ($v = 1; $v <= 3; $v++) {
                $vendor = StoreVendor::create([
                    'company_id'  => $company['id'],
                    'vendor_name' => 'Vendor ' . $v . ' of ' . $company['company_name'],
                ]);
                $vendorIds[] = $vendor->vendor_name; 
            }

            for ($i = 1; $i <= 10; $i++) {
                Item::create([
                    'company_id'          => $company['id'],
                    'item_code'           => $i,
                    'name'                => 'Item ' . $i . ' for ' . $company['company_name'],
                    'quantity_count'      => rand(50, 500),
                    'measurement'         => 'kg',
                    'purchase_date'       => now()->subDays(rand(1, 30)),
                    'date_of_manufacture' => now()->subMonths(1),
                    'date_of_expiry'      => now()->addMonths(6),
                    'brand_name'          => 'Brand ' . chr(64 + $i),
                    'replacement'         => 'Replace after 6 months',
                    'category'            => 'Category ' . rand(1, 3),
                    'vendor_name'         => $vendorIds[array_rand($vendorIds)],
                    'availability_stock'  => rand(10, 100),
                    'cost_price'          => rand(100, 500),
                    'selling_price'       => rand(600, 900),
                    'images'              => json_encode([]),
                    'catalog'             => false,
                    'online_visibility'   => true,
                ]);
            }
        }
    }
}
