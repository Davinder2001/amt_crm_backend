<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VariantResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                     => $this->id,
            'variant_regular_price'  => $this->variant_regular_price,
            'variant_sale_price'     => $this->variant_sale_price,
            'variant_stock'          => $this->stock,
            'variant_units_in_peace' => $this->variant_units_in_peace,
            'variant_price_per_unit' => $this->variant_price_per_unit,
            'images'                 => $this->images,
            'attributes'             => $this->attributeValues->map(fn($v) => [
                'attribute' => $v->attribute->name,
                'value'     => $v->value,
            ]),
        ];
    }
}
