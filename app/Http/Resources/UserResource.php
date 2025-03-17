<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'uid'              => $this->uid,
            'email'         => $this->email,
            'number'        => $this->number,
            'user_type'     => $this->user_type,
            'company_id'    => $this->company_id,
            'company_name'  => $this->company_name,
            'company_slug'  => $this->company->company_slug,
            'roles'         => RoleResource::collection($this->whenLoaded('roles')),
            'meta'          => $this->meta->pluck('meta_value', 'meta_key'),
        ];
    }
}
