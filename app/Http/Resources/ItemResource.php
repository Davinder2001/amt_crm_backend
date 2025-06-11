<?php

// namespace App\Http\Resources;

// use Illuminate\Http\Resources\Json\JsonResource;

// class ItemResource extends JsonResource
// {
//     public function toArray($request)
//     {
//         return [
//             'id'                  => $this->id,
//             'company_id'          => $this->company_id,
//             'item_code'           => $this->item_code,
//             'name'                => $this->name,
//             'quantity_count'      => $this->quantity_count,
//             'measurement'         => $this->measurement,
//             'purchase_date'       => $this->purchase_date ? $this->purchase_date->format('Y-m-d') : null,
//             'date_of_manufacture' => $this->date_of_manufacture ? $this->date_of_manufacture->format('Y-m-d') : null,
//             'date_of_expiry'      => $this->date_of_expiry ? $this->date_of_expiry->format('Y-m-d') : null,
//             'brand_name'          => $this->brand_name,
//             'replacement'         => $this->replacement,
//             'categories'          => $this->whenLoaded('categories', function () {
//                 return $this->categories->map(function ($category) {
//                     return [
//                         'id'   => $category->id,
//                         'name' => $category->name,
//                     ];
//                 });
//             }),
//             'vendor_name'         => $this->vendor_name,
//             'availability_stock'  => $this->availability_stock,
//             'images'              => $this->images,
//             'catalog'             => (bool) $this->catalog,
//             'online_visibility'   => (bool) $this->online_visibility,
//             'cost_price'          => $this->cost_price,
//             'selling_price'       => $this->selling_price,
//             'created_at'          => $this->created_at ? $this->created_at->format('Y-m-d') : null,
//             'updated_at'          => $this->updated_at ? $this->updated_at->format('Y-m-d') : null,
//             'taxes'               => $this->whenLoaded('taxes', function () {
//                 return $this->taxes->map(function ($tax) {
//                     return [
//                         'id'   => $tax->id,
//                         'name' => $tax->name,
//                         'rate' => $tax->rate,
//                     ];
//                 });
//             }),

//             'variants'            => $this->whenLoaded('variants', function () {
//                 return $this->variants->map(function ($variant) {
//                     return [
//                         'id'         => $variant->id,
//                         'price'      => $variant->price,
//                         'stock'      => $variant->stock,
//                         'images'     => $variant->images,
//                         'attributes' => $variant->attributeValues->map(function ($val) {
//                             return [
//                                 'attribute' => $val->attribute->name,
//                                 'value'     => $val->value,
//                             ];
//                         }),
//                     ];
//                 });
//             }),
//         ];
//     }
// }



namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray($request)
    {
        $basePrice = $this->selling_price;
        if ($this->relationLoaded('variants') && $this->variants->isNotEmpty()) {
            $basePrice = $this->variants->first()->price;
        }

        $totalTaxRate = 0;
        if ($this->relationLoaded('taxes') && $this->taxes->isNotEmpty()) {
            $totalTaxRate = $this->taxes->sum('rate');
        }

        $finalCost = round(
            $basePrice * (1 + $totalTaxRate / 100),
            2
        );

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
            'replacement'         => $this->replacement,
            'featured_image'      => $this->featured_image,

            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->map(fn($category) => [
                    'id'   => $category->id,
                    'name' => $category->name,
                ]);
            }),

            'vendor_name'        => $this->vendor_name,
            'availability_stock' => $this->availability_stock,
            'images'             => $this->images,
            'catalog'            => (bool) $this->catalog,
            'online_visibility'  => (bool) $this->online_visibility,
            'cost_price'         => $this->cost_price,
            'selling_price'      => $this->selling_price,
            'final_cost'         => $finalCost,
            'created_at' => optional($this->created_at)->format('Y-m-d'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d'),

            'taxes' => $this->whenLoaded('taxes', function () {
                return $this->taxes->map(fn($tax) => [
                    'id'   => $tax->id,
                    'name' => $tax->name,
                    'rate' => $tax->rate,
                ]);
            }),

            'variants' => $this->whenLoaded('variants', function () use ($totalTaxRate) {
                return $this->variants->map(function ($variant) use ($totalTaxRate) {
                    $variantFinal = round(
                        $variant->price * (1 + $totalTaxRate / 100),
                        2
                    );
                    return [
                        'id'            => $variant->id,
                        'ragular_price' => $variant->ragular_price,
                        'price'         => $variant->price,
                        'stock'         => $variant->stock,
                        'images'        => $variant->images,
                        'final_cost'    => $variantFinal,
                        'attributes'    => $variant->attributeValues->map(fn($val) => [
                            'attribute' => $val->attribute->name,
                            'value'     => $val->value,
                        ]),
                    ];
                });
            }),
            'batches' => $this->whenLoaded('batches', function () {
                return $this->batches->map(function ($batch) {
                    return [
                        'id'            => $batch->id,
                        'batch_number'  => $batch->batch_number,
                        'purchase_price' => $batch->purchase_price,
                        'quantity'      => $batch->quantity,
                        'created_at'    => optional($batch->created_at)->format('Y-m-d H:i'),
                    ];
                });
            }),

        ];
    }
}
