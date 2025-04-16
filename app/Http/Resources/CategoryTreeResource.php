<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryTreeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'children'   => CategoryTreeResource::collection($this->whenLoaded('childrenRecursive')),
            'items'      => ItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
