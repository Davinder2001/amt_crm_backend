<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreVendorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'vendor_name'       => $this->vendor_name,
            'vendor_number'     => $this->vendor_number,
            'vendor_email'      => $this->vendor_email,
            'vendor_address'    => $this->vendor_address,
        ];
    }
}
