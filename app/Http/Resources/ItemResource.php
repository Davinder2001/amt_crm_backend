<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray($request)
    {
        /* ───── 1. Common helpers ───── */
        $taxRate = $this->relationLoaded('taxes') && $this->taxes->isNotEmpty()
            ? (float) $this->taxes->sum('rate')
            : 0.0;

        $calcFinal = fn (float $base) => round($base * (1 + $taxRate / 100), 2);

        /* ───── 2. Item-level base & final cost ───── */
        $itemBase = null;

        if ($this->product_type === 'simple_product') {
            $itemBase = $this->sale_price ?? $this->regular_price;
        } elseif ($this->relationLoaded('variable_product') && $this->variants->isNotEmpty()) {
            // For variable products we’ll expose the *lowest* variant price at item level
            $itemBase = $this->variants
                ->map(fn ($v) => $v->sale_price ?? $v->regular_price ?? $v->price)
                ->filter()          // drop nulls
                ->min();            // pick the minimum
        }

        $itemFinalCost = $itemBase !== null ? $calcFinal($itemBase) : null;

        /* ───── 3. Build the response ───── */
        return [
            'id'                  => $this->id,
            'company_id'          => $this->company_id,
            'item_code'           => $this->item_code,
            'name'                => $this->name,
            'quantity_count'      => $this->quantity_count,
            'measurement'         => $this->measurement,
            'purchase_date'       => optional($this->purchase_date)->format('Y-m-d'),
            'date_of_manufacture' => optional($this->date_of_manufacture)->format('Y-m-d'),
            'date_of_expiry'      => optional($this->date_of_expiry)->format('Y-m-d'),
            'brand_id'            => $this->brand_id,
            'brand_name'          => $this->brand_name,
            'product_type'        => $this->product_type,
            'replacement'         => $this->replacement,
            'featured_image'      => $this->featured_image,

            'categories' => $this->whenLoaded('categories', fn () =>
                $this->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ),

            'vendor_name'        => $this->vendor_name,
            'availability_stock' => $this->availability_stock,
            'images'             => $this->images,
            'catalog'            => (bool) $this->catalog,
            'online_visibility'  => (bool) $this->online_visibility,
            'cost_price'         => $this->cost_price,
            'regular_price'      => $this->regular_price,
            'sale_price'         => $this->sale_price,
            'final_cost'         => $itemFinalCost,

            'created_at' => optional($this->created_at)->format('Y-m-d'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d'),

            'taxes' => $this->whenLoaded('taxes', fn () =>
                $this->taxes->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'rate' => $t->rate])
            ),

            'variants' => $this->whenLoaded('variants', function () use ($calcFinal) {
                return $this->variants->map(function ($variant) use ($calcFinal) {
                    $base = $variant->sale_price ?? $variant->regular_price ?? $variant->price;
                    return [
                        'id'            => $variant->id,
                        'regular_price' => $variant->regular_price,
                        'sale_price'    => $variant->sale_price,
                        'price'         => $variant->price,
                        'stock'         => $variant->stock,
                        'images'        => $variant->images,
                        'final_cost'    => $base !== null ? $calcFinal($base) : null,
                        'attributes'    => $variant->attributeValues->map(fn ($v) => [
                            'attribute' => $v->attribute->name,
                            'value'     => $v->value,
                        ]),
                    ];
                });
            }),

            'batches' => $this->whenLoaded('batches', fn () =>
                $this->batches->map(fn ($b) => [
                    'id'            => $b->id,
                    'batch_number'  => $b->batch_number,
                    'purchase_price'=> $b->purchase_price,
                    'quantity'      => $b->quantity,
                    'created_at'    => optional($b->created_at)->format('Y-m-d H:i'),
                ])
            ),
        ];
    }
}
