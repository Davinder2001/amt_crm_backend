<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'invoice_number'=> $this->invoice_number,
            'client_name'   => $this->client_name,
            'final_amount'  => $this->final_amount,
            'invoice_date'  => $this->invoice_date,
            'payment_method'=> $this->payment_method,
            'items'         => InvoiceItemResource::collection($this->whenLoaded('items')),
            'credit'        => $this->whenLoaded('credit'),
        ];
    }
}
