<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\{
    BatchResource,
    VariantResource,
    TaxResource,
    CategoryResource,
    ItemMeasurementResource
};

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
            'id'                => $this->id,
            'item_code'         => $this->item_code,
            'name'              => $this->name,
            'featured_image'    => $this->featured_image,
            'images'            => $this->images,
            'availability_stock' => $this->availability_stock,
            'catalog'           => (bool) $this->catalog,
            'online_visibility' => (bool) $this->online_visibility,

            'regular_price'     => $this->regular_price,
            'sale_price'        => $this->sale_price,
            'final_cost'        => $itemFinalCost,

            'brand'             => new ItemBrandResource($this->whenLoaded('brand')),
            'measurement'       => new ItemMeasurementResource($this->whenLoaded('measurementDetails')),

            'categories'        => CategoryResource::collection($this->whenLoaded('categories')),
            'taxes'             => TaxResource::collection($this->whenLoaded('taxes')),
            'variants'          => VariantResource::collection($this->whenLoaded('variants')),
            'batches'           => BatchResource::collection($this->whenLoaded('batches')),
        ];
    }
}
