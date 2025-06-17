<?php

namespace App\Services;

use App\Models\{
    Category,
    CategoryItem,
    Item,
    ItemBatch,
    ItemTax,
    Tax
};
use Illuminate\Support\Facades\DB;

class ItemService
{
    /* --------------------------------------------------------------
     |  Item code helpers
     * -------------------------------------------------------------- */
    public static function generateNextItemCode(int $companyId): int
    {
        return (int) Item::where('company_id', $companyId)
            ->select(DB::raw('MAX(CAST(item_code AS UNSIGNED)) as max_code'))
            ->value('max_code') + 1;
    }

    /* --------------------------------------------------------------
     |  Categories
     * -------------------------------------------------------------- */
    public static function assignCategories(Item $item, ?array $categories, int $companyId): void
    {
        if (!$categories || empty($categories)) {
            $categories = [
                Category::firstOrCreate([
                    'company_id' => $companyId,
                    'name'       => 'Uncategorized',
                ])->id,
            ];
        }

        $item->categories()->syncWithoutDetaching($categories);
    }

    /* --------------------------------------------------------------
     |  Tax
     * -------------------------------------------------------------- */
    public static function assignTax(Item $item, $taxId): void
    {
        if (!$taxId || !Tax::find($taxId)) return;

        ItemTax::updateOrCreate(
            ['store_item_id' => $item->id],
            ['tax_id' => $taxId]
        );
    }

    /* --------------------------------------------------------------
     |  Batch / stock
     * -------------------------------------------------------------- */
    public static function createBatch(Item $item, array $data): void
    {
        ItemBatch::updateOrCreate(
            [
                'company_id' => $data['company_id'],
                'item_id'    => $item->id,
            ],
            [
                'batch_number'        => $data['batch_number'] ?? 'BATCH-' . strtoupper(uniqid()),
                'purchase_price'      => $data['cost_price'],
                'quantity'            => $data['quantity_count'],
                'unit_of_measure'     => $data['unit_of_measure'] ?? null,
                'units_in_peace'      => $data['units_in_peace']  ?? null,
                'price_per_unit'      => $data['price_per_unit']  ?? null,
                'date_of_manufacture' => $data['date_of_manufacture'] ?? null,
                'date_of_expiry'      => $data['date_of_expiry']      ?? null,
            ]
        );
    }

    /* --------------------------------------------------------------
     |  Variants
     * -------------------------------------------------------------- */
    public static function createItemVariants(Item $item, array $variants, array $imageLinks = []): void
    {
        foreach ($variants as $variantData) {
            $variant = $item->variants()->create([
                'variant_regular_price'  => $variantData['variant_regular_price'],
                'variant_sale_price'     => $variantData['variant_sale_price']     ?? null,
                'stock'                  => $variantData['variant_stock']          ?? 0,
                'variant_units_in_peace' => $variantData['variant_units_in_peace'] ?? null,
                'variant_price_per_unit' => $variantData['variant_price_per_unit'] ?? null,
                'images'                 => $imageLinks,
            ]);

            if (!empty($variantData['attributes'])) {
                foreach ($variantData['attributes'] as $attr) {
                    $variant->attributeValues()->attach(
                        $attr['attribute_value_id'],
                        ['attribute_id' => $attr['attribute_id']]
                    );
                }
            }
        }
    }
}
