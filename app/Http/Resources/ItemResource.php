<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->id,
            'company_id'          => $this->company_id,
            'item_code'           => $this->item_code,
            'name'                => $this->name,
            'quantity_count'      => $this->quantity_count,
            'measurement'         => $this->measurement,
            'purchase_date'       => $this->purchase_date ? $this->purchase_date->format('Y-m-d') : null,
            'date_of_manufacture' => $this->date_of_manufacture ? $this->date_of_manufacture->format('Y-m-d') : null,
            'date_of_expiry'      => $this->date_of_expiry ? $this->date_of_expiry->format('Y-m-d') : null,
            'brand_name'          => $this->brand_name,
            'replacement'         => $this->replacement,
            'categories'          => $this->whenLoaded('categories', function () {
                return $this->categories->map(function ($category) {
                    return [
                        'id'   => $category->id,
                        'name' => $category->name,
                    ];
                });
            }),
            'vendor_name'         => $this->vendor_name,
            'availability_stock'  => $this->availability_stock,
            'images'              => $this->images,
            'catalog'             => (bool) $this->catalog,
            'online_visibility'   => (bool) $this->online_visibility,
            'cost_price'          => $this->cost_price,
            'selling_price'       => $this->selling_price,
            'created_at'          => $this->created_at ? $this->created_at->format('Y-m-d') : null,
            'updated_at'          => $this->updated_at ? $this->updated_at->format('Y-m-d') : null,
            'taxes'               => $this->whenLoaded('taxes', function () {
                return $this->taxes->map(function ($tax) {
                    return [
                        'id'   => $tax->id,
                        'name' => $tax->name,
                        'rate' => $tax->rate,
                    ];
                });
            }),

            'variants'            => $this->whenLoaded('variants', function () {
                return $this->variants->map(function ($variant) {
                    return [
                        'id'         => $variant->id,
                        'price'      => $variant->price,
                        'stock'      => $variant->stock,
                        'images'     => $variant->images,
                        'attributes' => $variant->attributeValues->map(function ($val) {
                            return [
                                'attribute' => $val->attribute->name,
                                'value'     => $val->value,
                            ];
                        }),
                    ];
                });
            }),
        ];
    }
}
