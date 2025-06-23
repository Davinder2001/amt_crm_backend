<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\{
    BatchResource,
    TaxResource,
    CategoryResource,
    ItemBrandResource,
    ItemMeasurementResource
};

class ItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                 => $this->id,
            'item_code'          => $this->item_code,
            'name'               => $this->name,
            'featured_image'     => $this->featured_image,
            'images'             => $this->images,
            'availability_stock' => $this->availability_stock,
            'catalog'            => (bool) $this->catalog,
            'online_visibility'  => (bool) $this->online_visibility,

            'brand'              => new ItemBrandResource($this->whenLoaded('brand')),
            'measurement'        => new ItemMeasurementResource($this->whenLoaded('measurementDetails')),
            'categories'         => CategoryResource::collection($this->whenLoaded('categories')),
            'taxes'              => TaxResource::collection($this->whenLoaded('taxes')),
            'batches'            => BatchResource::collection($this->whenLoaded('batches')),
        ];
    }
}
