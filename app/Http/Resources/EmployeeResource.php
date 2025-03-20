<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $company = $this->companies->first(); 

        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'number'        => $this->number,
            'user_type'     => $this->user_type,
            'user_status'   => $this->user_status,
            'company_name'    => $company ? $company->name : null,
            'company_id'    => $company ? $company->id : null,
            'company_slug'  => $company ? $company->company_slug : null,
            'roles'         => RoleResource::collection($this->whenLoaded('roles')),
            'meta'          => $this->meta->pluck('meta_value', 'meta_key'),
        ];
    }
}
