<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'uid'        => $this->uid,
            'email'      => $this->email,
            'number'     => $this->number,
            'user_type'  => $this->user_type,
            'roles'      => RoleResource::collection($this->whenLoaded('roles')),
            'meta'       => $this->whenLoaded('meta', function () {
                                return $this->meta->pluck('meta_value', 'meta_key');
                            }, []),
            'companies'  => CompanyResource::collection($this->whenLoaded('companies')),
        ];
    }
}
