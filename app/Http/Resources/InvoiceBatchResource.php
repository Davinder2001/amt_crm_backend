<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceBatchResource extends JsonResource
{
    public function toArray($request)
    {
        // Fetch tax percentage from related item if loaded
        $taxPercent = $this->relationLoaded('item') && $this->item->relationLoaded('taxes')
            ? $this->item->taxes->sum('percentage')
            : 0;

        $taxRate = $taxPercent / 100;
        $isExcluded = $this->tax_type === 'exclude';

        $cost    = (float) $this->cost_price;
        $regular = (float) $this->regular_price;
        $sale    = (float) $this->sale_price;

        // Apply tax only to regular and sale prices if tax is excluded
        if ($isExcluded && $taxRate > 0) {
            $regular += $regular * $taxRate;
            $sale += $sale * $taxRate;
        }

        return [
            'id'                     => $this->id,
            'cost_price'             => round($cost, 2),
            'regular_price'          => round($regular, 2),
            'sale_price'             => round($sale, 2),

            'quantity'               => $this->quantity,
            'product_type'           => $this->product_type,
            'purchase_date'          => $this->purchase_date,
            'date_of_manufacture'    => $this->date_of_manufacture,
            'date_of_expiry'         => $this->date_of_expiry,
            'replacement'            => $this->replacement,
            'invoice_number'         => $this->invoice_number,
            'tax_type'               => $this->tax_type,
            'unit_of_measure'        => $this->unit_of_measure,

            'vendor'                 => new StoreVendorResource($this->whenLoaded('vendor')),
            'variants' => InvoiceVariantResource::collection($this->whenLoaded('variants')),

        ];
    }
}
