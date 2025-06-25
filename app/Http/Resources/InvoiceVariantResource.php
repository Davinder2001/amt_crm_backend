<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceVariantResource extends JsonResource
{
    public function toArray($request)
    {

        $batch = $this->batch;
        $item  = $batch?->item;
       

        // Safely calculate tax percentage from item if loaded
        $taxPercent = $item && $item->relationLoaded('taxes')
            ? $item->taxes->sum('percentage')
            : 0;

        $taxRate = $taxPercent / 100;
        $isExcluded = $batch && $batch->tax_type === 'exclude';

        // Original values
        $regular = (float) $this->variant_regular_price;
        $sale    = (float) $this->variant_sale_price;
        $perUnit = (float) $this->variant_price_per_unit;

        // Apply tax if excluded
        if ($isExcluded && $taxRate > 0) {
            $regular += $regular * $taxRate;
            $sale    += $sale * $taxRate;
            $perUnit += $perUnit * $taxRate;
        }

        return [
            'id'                     => $this->id,
            'variant_regular_price'  => round($regular, 2),
            'variant_sale_price'     => round($sale, 2),
            'variant_price_per_unit' => round($perUnit, 2),
            'variant_stock'          => $this->stock,
            'variant_units_in_peace' => $this->variant_units_in_peace,
            'images'                 => $this->images,

            'attributes' => $this->attributeValues->map(fn($v) => [
                'attribute' => $v->attribute->name,
                'value'     => $v->value,
            ]),
        ];
    }
}
