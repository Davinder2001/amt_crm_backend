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

        $totalStock = $this->whenLoaded('batches', function () {
            return $this->batches->sum('stock');
        });

        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'featured_image'     => $this->featured_image,
            'images'             => $this->images,
            'availability_stock' => $totalStock,

            'brand'              => new ItemBrandResource($this->whenLoaded('brand')),
            'measurement'        => new ItemMeasurementResource($this->whenLoaded('measurementDetails')),
            'categories'         => CategoryResource::collection($this->whenLoaded('categories')),
            'taxes'              => TaxResource::collection($this->whenLoaded('taxes')),
            'batches'            => BatchResource::collection($this->whenLoaded('batches')),
        ];
    }
}
