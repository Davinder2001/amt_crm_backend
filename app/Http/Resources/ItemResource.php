<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray($request)
    {
        $taxRate = $this->relationLoaded('taxes') && $this->taxes->isNotEmpty()
            ? (float) $this->taxes->sum('rate')
            : 0.0;

        $addTax = fn(float $base) => round($base * (1 + $taxRate / 100), 2);

        $itemFinalCost = $this->sale_price
            ?? ($this->regular_price !== null ? $addTax($this->regular_price) : null);

        return [
            'id'             => $this->id,
            'company_id'     => $this->company_id,
            'item_code'      => $this->item_code,
            'name'           => $this->name,

            'measurement'    => [
                'id'   => $this->measurement,
                'name' => $this->measurementDetails->name ?? null,
            ],

            'brand_id'       => $this->brand_id,
            'featured_image' => $this->featured_image,
            'images'         => $this->images,
            'availability_stock' => $this->availability_stock,
            'catalog'        => (bool) $this->catalog,
            'online_visibility' => (bool) $this->online_visibility,

            'regular_price'  => $this->regular_price,
            'sale_price'     => $this->sale_price,
            'final_cost'     => $itemFinalCost,

            'categories' => $this->whenLoaded('categories', fn() =>
                $this->categories->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                ])
            ),

            'taxes' => $this->whenLoaded('taxes', fn() =>
                $this->taxes->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'rate' => $t->rate,
                ])
            ),

            'variants' => $this->whenLoaded('variants', function () use ($addTax) {
                return $this->variants->map(function ($variant) use ($addTax) {
                    $finalCost = $variant->variant_sale_price
                        ?? ($variant->variant_regular_price !== null
                            ? $addTax($variant->variant_regular_price)
                            : null);

                    return [
                        'id'                     => $variant->id,
                        'variant_regular_price'  => $variant->variant_regular_price,
                        'variant_sale_price'     => $variant->variant_sale_price,
                        'variant_stock'          => $variant->stock,
                        'variant_units_in_peace' => $variant->variant_units_in_peace,
                        'variant_price_per_unit' => $variant->variant_price_per_unit !== null
                            ? $addTax((float) $variant->variant_price_per_unit)
                            : null,
                        'images'                 => $variant->images,
                        'final_cost'             => $finalCost,

                        'attributes' => $variant->attributeValues->map(fn($v) => [
                            'attribute' => $v->attribute->name,
                            'value'     => $v->value,
                        ]),
                    ];
                });
            }),

            'batches' => $this->whenLoaded('batches', fn() =>
                $this->batches->map(fn($b) => [
                    'id'                 => $b->id,
                    'batch_number'       => $b->batch_number,
                    'purchase_price'     => $b->purchase_price,
                    'quantity'           => $b->quantity,
                    'date_of_manufacture'=> optional($b->date_of_manufacture)->format('Y-m-d'),
                    'date_of_expiry'     => optional($b->date_of_expiry)->format('Y-m-d'),
                    'created_at'         => optional($b->created_at)->format('Y-m-d H:i'),
                ])
            ),
        ];
    }
}
