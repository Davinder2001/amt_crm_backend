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
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'number'        => $this->number,
            'user_type'     => $this->user_type,
            'user_status'     => $this->user_status,
            'company_id'    => $this->company_id,
            'company_name'  => $this->company_name,
            'company_slug'  => $this->company->company_slug,
            'roles'         => RoleResource::collection($this->whenLoaded('roles')),
            'meta'          => $this->meta->pluck('meta_value', 'meta_key'),
        ];
    }
}
