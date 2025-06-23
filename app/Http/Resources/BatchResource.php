<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->id,
            'batch_number'        => $this->batch_number,
            'purchase_price'      => $this->purchase_price,
            'quantity'            => $this->quantity,
            'date_of_manufacture' => optional($this->date_of_manufacture)->format('Y-m-d'),
            'date_of_expiry'      => optional($this->date_of_expiry)->format('Y-m-d'),
            'created_at'          => optional($this->created_at)->format('Y-m-d H:i'),
        ];
    }
}
