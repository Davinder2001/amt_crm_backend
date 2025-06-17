<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray($request)
    {
        /*--------------------------------------------------------------
        | Helpers
        *--------------------------------------------------------------*/
        $taxRate = $this->relationLoaded('taxes') && $this->taxes->isNotEmpty()
            ? (float) $this->taxes->sum('rate')
            : 0.0;

        // add tax to a base number
        $addTax = fn (float $base) => round($base * (1 + $taxRate / 100), 2);

        /*--------------------------------------------------------------
        | Final cost (item level)
        *--------------------------------------------------------------*/
        $itemFinalCost = null;
        if ($this->sale_price !== null) {
            $itemFinalCost = $this->sale_price;          // use sale price directly
        } elseif ($this->regular_price !== null) {
            $itemFinalCost = $addTax($this->regular_price); // regular + tax
        }

        /*--------------------------------------------------------------
        | Payload
        *--------------------------------------------------------------*/
        return [
            'id'                  => $this->id,
            'company_id'          => $this->company_id,
            'item_code'           => $this->item_code,
            'invoice_id'          => $this->invoice_id,

            'name'                => $this->name,
            'quantity_count'      => $this->quantity_count,
            'measurement'         => $this->measurement,

            /* unit-meta */
            'unit_of_measure'     => $this->unit_of_measure,
            'units_in_peace'      => $this->units_in_peace,
            'price_per_unit'      => $this->price_per_unit,
            'sale_type'           => $this->sale_type,

            /* dates */
            'purchase_date'       => optional($this->purchase_date)->format('Y-m-d'),
            'date_of_manufacture' => optional($this->date_of_manufacture)->format('Y-m-d'),
            'date_of_expiry'      => optional($this->date_of_expiry)->format('Y-m-d'),

            /* brand / vendor */
            'brand_id'            => $this->brand_id,
            'brand_name'          => $this->brand_name,
            'product_type'        => $this->product_type,
            'replacement'         => $this->replacement,
            'featured_image'      => $this->featured_image,

            'categories' => $this->whenLoaded('categories', fn () =>
                $this->categories->map(fn ($c) => [
                    'id'   => $c->id,
                    'name' => $c->name,
                ])
            ),

            'vendor_name'        => $this->vendor_name,
            'availability_stock' => $this->availability_stock,
            'images'             => $this->images,
            'catalog'            => (bool) $this->catalog,
            'online_visibility'  => (bool) $this->online_visibility,

            /* base prices */
            'cost_price'    => $this->cost_price,
            'regular_price' => $this->regular_price,
            'sale_price'    => $this->sale_price,
            'final_cost'    => $itemFinalCost,

            /* taxes */
            'taxes' => $this->whenLoaded('taxes', fn () =>
                $this->taxes->map(fn ($t) => [
                    'id'   => $t->id,
                    'name' => $t->name,
                    'rate' => $t->rate,
                ])
            ),

            /* variants */
            'variants' => $this->whenLoaded('variants', function () use ($addTax) {
                return $this->variants->map(function ($variant) use ($addTax) {
                    /* final cost: sale or (regular + tax) */
                    $variantFinalCost = null;
                    if ($variant->variant_sale_price !== null) {
                        $variantFinalCost = $variant->variant_sale_price;
                    } elseif ($variant->variant_regular_price !== null) {
                        $variantFinalCost = $addTax($variant->variant_regular_price);
                    }

                    return [
                        'id'                     => $variant->id,
                        'variant_regular_price'  => $variant->variant_regular_price,
                        'variant_sale_price'     => $variant->variant_sale_price,
                        'variant_units_in_peace' => $variant->variant_units_in_peace,
                        'variant_price_per_unit' => $variant->variant_price_per_unit,
                        'variant_stock'          => $variant->stock,
                        'images'                 => $variant->images,
                        'final_cost'             => $variantFinalCost,

                        'attributes' => $variant->attributeValues->map(fn ($v) => [
                            'attribute' => $v->attribute->name,
                            'value'     => $v->value,
                        ]),
                    ];
                });
            }),

            /* batches */
            'batches' => $this->whenLoaded('batches', fn () =>
                $this->batches->map(fn ($b) => [
                    'id'                 => $b->id,
                    'batch_number'       => $b->batch_number,
                    'purchase_price'     => $b->purchase_price,
                    'quantity'           => $b->quantity,
                    'unit_of_measure'    => $b->unit_of_measure,
                    'units_in_peace'     => $b->units_in_peace,
                    'price_per_unit'     => $b->price_per_unit,
                    'date_of_manufacture'=> optional($b->date_of_manufacture)->format('Y-m-d'),
                    'date_of_expiry'     => optional($b->date_of_expiry)->format('Y-m-d'),
                    'created_at'         => optional($b->created_at)->format('Y-m-d H:i'),
                ])
            ),
        ];
    }
}
