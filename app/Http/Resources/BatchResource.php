<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray($request)
    {

        $totalStock = $this->whenLoaded('variant', function () {
            return $this->batches->sum('variant_stock');
        });

        return [
            'id'                  => $this->id,
            'cost_price'          => $this->cost_price,
            'quantity'            => $this->quantity,
            'stock'               => $totalStock ?? $this->quantity ?? 0,
            'product_type'        => $this->product_type,
            'purchase_date'       => $this->purchase_date,
            'date_of_manufacture' => $this->date_of_manufacture,
            'date_of_expiry'      => $this->date_of_expiry,
            'replacement'         => $this->replacement,
            'invoice_number'      => $this->invoice_number,
            'tax_type'            => $this->tax_type,
            'regular_price'       => $this->regular_price,
            'sale_price'          => $this->sale_price,
            'unit_of_measure'     => $this->unit_of_measure,
            'units_in_peace'      => $this->units_in_peace,
            'price_per_unit'      => $this->price_per_unit,

            'vendor'              => new StoreVendorResource($this->whenLoaded('vendor')),
            'variants'            => VariantResource::collection($this->whenLoaded('variants')),
        ];
    }
}
