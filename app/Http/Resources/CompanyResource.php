<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $baseImageUrl = config('app.image_uri');

        return [
            'id' => $this->id,
            'admin_id' => $this->admin_id,
            'company_name' => $this->company_name,
            'company_id' => $this->company_id,
            'company_slug' => $this->company_slug,
            'business_address' => $this->business_address,
            'pin_code' => $this->pin_code,
            'business_proof_type' => $this->business_proof_type,
            'business_id' => $this->business_id,
            'business_proof_front' => $this->business_proof_front
                ? $baseImageUrl . '/' . ltrim($this->business_proof_front, '/')
                : null,
            'business_proof_back' => $this->business_proof_back
                ? $baseImageUrl . '/' . ltrim($this->business_proof_back, '/')
                : null,
            'payment_status' => $this->payment_status,
            'verification_status' => $this->verification_status,
        ];
    }
}
