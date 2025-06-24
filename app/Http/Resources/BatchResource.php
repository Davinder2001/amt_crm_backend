<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->id,
            'cost_price'          => $this->cost_price,
            'quantity'            => $this->quantity,
            'product_type'        => $this->product_type,
            'purchase_date'       => $this->purchase_date,
            'date_of_manufacture' => $this->date_of_manufacture,
            'date_of_expiry'      => $this->date_of_expiry,
            'replacement'         => $this->replacement,
            'invoice_number'      => $this->invoice_number,
            'tax_type'            => $this->tax_type,
            'regular_price'       => $this->regular_price,
            'sale_price'          => $this->sale_price,

            'vendor'              => new StoreVendorResource($this->whenLoaded('vendor')),
            'variants'            => VariantResource::collection($this->whenLoaded('variants')),
        ];
    }
}
