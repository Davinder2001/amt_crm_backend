<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                 => $this->id,
            'company_id'         => $this->company_id,
            'item_code'          => $this->item_code,
            'name'               => $this->name,
            'quantity_count'     => $this->quantity_count,
            'measurement'        => $this->measurement,
            'purchase_date'      => $this->purchase_date,
            'date_of_manufacture'=> $this->date_of_manufacture,
            'date_of_expiry'     => $this->date_of_expiry,
            'brand_name'         => $this->brand_name,
            'replacement'        => $this->replacement,
            'category'           => $this->category,
            'vendor_name'        => $this->vendor_name,
            'availability_stock' => $this->availability_stock,
            'images'             => $this->images,
            'catalog'            => (bool) $this->catalog,
            'online_visibility'  => (bool) $this->online_visibility,
            'cost_price'         => $this->cost_price,
            'selling_price'      => $this->selling_price,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,

            'variants' => $this->whenLoaded('variants', function () {
                return $this->variants->map(function ($variant) {
                    return [
                        'id'    => $variant->id,
                        'price' => $variant->price,
                        'stock' => $variant->stock,
                        'images'=> $variant->images,
                        'attributes' => $variant->attributeValues->map(function ($val) {
                            return [
                                'attribute' => $val->attribute->name,
                                'value'     => $val->value,
                            ];
                        })
                    ];
                });
            }),
        ];
    }
}
