<?php

namespace App\Services;

use App\Models\{Category, CategoryItem, Item, ItemBatch, ItemTax, Tax};
use Illuminate\Support\Facades\DB;

class ItemService
{
    public static function generateNextItemCode(int $companyId): int
    {
        return (int) Item::where('company_id', $companyId)
            ->select(DB::raw('MAX(CAST(item_code AS UNSIGNED)) as max_code'))
            ->value('max_code') + 1;
    }

    public static function assignCategories(Item $item, ?array $categories, int $companyId): void
    {
        if (!$categories || empty($categories)) {
            $categories = [Category::firstOrCreate([
                'company_id' => $companyId,
                'name'       => 'Uncategorized',
            ])->id];
        }

        foreach ($categories as $categoryId) {
            CategoryItem::create([
                'store_item_id' => $item->id,
                'category_id'   => $categoryId,
            ]);
        }
    }

    public static function assignTax(Item $item, $taxId): void
    {
        if (!$taxId || !Tax::find($taxId)) return;

        ItemTax::updateOrCreate(
            ['store_item_id' => $item->id],
            ['tax_id'        => $taxId]
        );
    }

    public static function createBatch(Item $item, array $data): void
    {
        ItemBatch::create([
            'company_id'     => $data['company_id'],
            'item_id'        => $item->id,
            'batch_number'   => 'BATCH-' . strtoupper(uniqid()),
            'purchase_price' => $data['cost_price'],
            'quantity'       => $data['quantity_count'],
        ]);
    }



    public static function createItemVariants(Item $item, array $variants, array $imageLinks): void
    {
        foreach ($variants as $variantData) {
            $variant = $item->variants()->create([
                'regular_price' => $variantData['regular_price'],
                'price'         => $variantData['sale_price'] ?? 0,
                'stock'         => $variantData['stock'] ?? 1,
                'images'        => $imageLinks,
            ]);

            foreach ($variantData['attributes'] as $attribute) {
                $variant->attributeValues()->attach($attribute['attribute_value_id'], [
                    'attribute_id' => $attribute['attribute_id'],
                ]);
            }
        }
    }
}
