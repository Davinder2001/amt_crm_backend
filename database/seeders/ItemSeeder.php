<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\Category;
use App\Models\ItemBatch;
use App\Models\ItemVariant;
use App\Models\Attribute;
use App\Models\AttributeValue;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $companies = [
                ['id' => 1, 'company_name' => 'Demo Admin Company One'],
                ['id' => 2, 'company_name' => 'Demo Admin Company Two'],
                ['id' => 3, 'company_name' => 'Demo Admin Company Three'],
                ['id' => 4, 'company_name' => 'Demo Admin Company Four'],
                ['id' => 5, 'company_name' => 'Demo Admin Company Five'],
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
                $companyId = $company['id'];
                $categoryMap = [];

                // ✅ 1. Create attributes for this company
                $attributeData = [
                    'Color'    => ['Red', 'Blue', 'Green', 'Black'],
                    'Size'     => ['S', 'M', 'L', 'XL'],
                    'Material' => ['Cotton', 'Polyester', 'Leather']
                ];

                $attributesMap = [];

                foreach ($attributeData as $attrName => $values) {
                    $attribute = Attribute::create([
                        'company_id' => $companyId,
                        'name'       => $attrName,
                    ]);

                    foreach ($values as $value) {
                        $attrValue = AttributeValue::create([
                            'attribute_id' => $attribute->id,
                            'value'        => $value
                        ]);
                        $attributesMap[$attribute->id][] = $attrValue->id;
                    }
                }

                // ✅ 2. Create categories for the company
                foreach ($baseCategories as $parent => $subs) {
                    $parentCat = Category::create([
                        'company_id' => $companyId,
                        'name'       => $parent,
                        'parent_id'  => null,
                    ]);

                    foreach ($subs as $sub) {
                        $childCat = Category::create([
                            'company_id' => $companyId,
                            'name'       => $sub,
                            'parent_id'  => $parentCat->id,
                        ]);
                        $categoryMap[$sub] = $childCat->id;
                    }
                }

                // ✅ 3. Create items + batches + variants
                $itemIndex = 1;

                foreach ($sampleProducts as $subCategory => $productNames) {
                    if (!isset($categoryMap[$subCategory])) continue;

                    foreach ($productNames as $productName) {
                        if ($itemIndex > 25) break;

                        $item = Item::create([
                            'company_id'         => $companyId,
                            'item_code'          => 'ITEM-' . $itemIndex,
                            'name'               => $productName,
                            'measurement'        => null,
                            'featured_image'     => null,
                            'availability_stock' => rand(10, 100),
                            'images'             => [],
                            'catalog'            => (bool)rand(0, 1),
                            'online_visibility'  => true,
                        ]);

                        // Attach categories
                        $attachIds = [$categoryMap[$subCategory]];
                        $otherCats = array_diff_key($categoryMap, [$subCategory => true]);
                        if (!empty($otherCats) && rand(0, 1)) {
                            $randomOther = array_rand($otherCats);
                            $attachIds[] = $categoryMap[$randomOther];
                        }
                        $item->categories()->attach($attachIds);

                        // Add 2 batches per item
                        for ($b = 1; $b <= 2; $b++) {
                            $batch = ItemBatch::create([
                                'company_id'          => $companyId,
                                'item_id'             => $item->id,
                                'invoice_number'      => 'INV-' . strtoupper(Str::random(5)),
                                'quantity'            => 20,
                                'stock'               => 20,
                                'purchase_date'       => now()->subDays(rand(1, 30)),
                                'date_of_manufacture' => now()->subDays(rand(30, 60)),
                                'date_of_expiry'      => now()->addDays(rand(180, 360)),
                                'replacement'         => 'Standard warranty',
                                'cost_price'          => rand(5000, 20000),
                                'product_type'        => 'variable_product',
                                'vendor_id'           => null,
                                'unit_of_measure'     => 'piece',
                                'regular_price'       => rand(6000, 22000),
                                'sale_price'          => rand(5500, 21000),
                                'tax_type'            => 'include',
                            ]);

                            // Add 2 variants per batch
                            for ($v = 1; $v <= 2; $v++) {
                                $variant = ItemVariant::create([
                                    'batch_id'               => $batch->id,
                                    'item_id'                => $item->id,
                                    'variant_regular_price'  => rand(6000, 22000),
                                    'variant_sale_price'     => rand(5500, 21000),
                                    'variant_units_in_peace' => rand(1, 5),
                                    'variant_price_per_unit' => rand(1000, 3000),
                                    'stock'                  => rand(5, 20),
                                    'images'                 => [],
                                ]);

                                // Attach one attribute-value to variant
                                $attributeId = array_rand($attributesMap);
                                $valueIds = $attributesMap[$attributeId];
                                $valueId = $valueIds[array_rand($valueIds)];

                                $variant->attributeValues()->sync([
                                    $valueId => ['attribute_id' => $attributeId]
                                ]);
                            }
                        }

                        $itemIndex++;
                    }

                    if ($itemIndex > 25) break;
                }
            }
        });
    }
}
