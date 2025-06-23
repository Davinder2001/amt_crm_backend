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
     |  Batch / stock
     * -------------------------------------------------------------- */
    public static function createBatch(Item $item, array $data): void
    {
        ItemBatch::create([
            'company_id'           => $data['company_id'],
            'item_id'              => $item->id,
            'batch_number'         => $data['batch_number'] ?? 'BATCH-' . strtoupper(uniqid()),
            'purchase_price'       => $data['cost_price'],
            'quantity'             => $data['quantity_count'],
        ]);
    }

    public static function updateBatch(Item $item, array $data): void
    {
        $batch = $item->batches()->first();
        if (!$batch) return;

        $batch->update([
            'purchase_price'       => $data['cost_price'] ?? $batch->purchase_price,
            'quantity'             => $data['quantity_count'] ?? $batch->quantity,
        ]);
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
