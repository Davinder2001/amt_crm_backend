<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemTableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'quantity_count'      => $this->quantity_count,
            'date_of_expiry'      => optional($this->date_of_expiry)->format('Y-m-d'),
            'final_cost'         => $this->final_cost,
            'vendor_name'        => $this->vendor_name,
            'availability_stock' => $this->availability_stock,
            'quantity_count'      => $this->quantity_count,

        ];
    }
}
