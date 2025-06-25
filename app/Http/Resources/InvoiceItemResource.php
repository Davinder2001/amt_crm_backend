<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'item_code'          => $this->item_code,
            'name'               => $this->name,
            'featured_image'     => $this->featured_image,
            'images'             => $this->images,
            'availability_stock' => $this->availability_stock,
            'catalog'            => (bool) $this->catalog,
            'units_in_peace'      => $this->units_in_peace,
            'price_per_unit'      => $this->price_per_unit,
            'online_visibility'  => (bool) $this->online_visibility,

            'brand'              => new ItemBrandResource($this->whenLoaded('brand')),
            'measurement'        => new ItemMeasurementResource($this->whenLoaded('measurementDetails')),
            'categories'         => CategoryResource::collection($this->whenLoaded('categories')),
            'taxes'              => TaxResource::collection($this->whenLoaded('taxes')),
            'batches'            => InvoiceBatchResource::collection($this->whenLoaded('batches')),
        ];
    }
}
