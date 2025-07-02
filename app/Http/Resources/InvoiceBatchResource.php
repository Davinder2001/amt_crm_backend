<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceBatchResource extends JsonResource
{
    public function toArray($request)
    {
        $taxes = collect();
        $taxPercent = 0;

        if ($this->item) {
            // Always try fetching taxes (either already loaded or fetch from DB)
            $taxes = $this->item->taxes ?? $this->item->taxes()->get();
            $taxPercent = $taxes->sum('rate');
        }

        $taxRate = $taxPercent / 100;
        $isExcluded = $this->tax_type === 'exclude';

        $regular = (float) $this->regular_price;
        $sale    = (float) $this->sale_price;
        $ppu     = (float) $this->price_per_unit;

        if ($isExcluded && $taxRate > 0) {
            $regular += $regular * $taxRate;
            $sale += $sale * $taxRate;
            $ppu += $ppu * $taxRate;
        }

        return [
            'id'                    => $this->id,
            'cost_price'            => $this->cost_price,
            'regular_price'         => round($regular, 2),
            'sale_price'            => round($sale, 2),
            'price_per_unit'        => round($ppu, 2),

            'quantity'              => $this->quantity,
            'stock'                 => $this->stock,
            'product_type'          => $this->product_type,
            'purchase_date'         => $this->purchase_date,
            'date_of_manufacture'   => $this->date_of_manufacture,
            'date_of_expiry'        => $this->date_of_expiry,
            'replacement'           => $this->replacement,
            'invoice_number'        => $this->invoice_number,
            'tax_type'              => $this->tax_type,
            'unit_of_measure'       => $this->unit_of_measure,
            'units_in_peace'        => $this->units_in_peace,

            'vendor'                => new StoreVendorResource($this->whenLoaded('vendor')),
            'variants'              => InvoiceVariantResource::collection($this->whenLoaded('variants')),
        ];
    }
}
