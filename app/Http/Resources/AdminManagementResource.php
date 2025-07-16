<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminManagementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'uid'               => $this->uid,
            'name'              => $this->name,
            'email'             => $this->email,
            'number'            => $this->number,
            'profile_image'     => $this->profile_image,
            'user_type'         => $this->user_type,
            'user_status'       => $this->user_status,
            'company_id'        => $this->company_id,
            'email_verified_at' => $this->email_verified_at,
            'created_at'        => $this->created_at,
            'roles'             => $this->roles->map(function ($role) {
                return [
                    'id'          => $role->id,
                    'name'        => $role->name,
                    'company_id'  => $role->company_id,
                    'permissions' => $role->permissions->pluck('name'),
                ];
            }),
            'companies' => $this->whenLoaded('companies', function () {
                return $this->companies->map(function ($company) {
                    return [
                        'id'   => $company->id,
                        'name' => $company->company_name,
                        'slug' => $company->company_slug,
                    ];
                });
            }),
        ];
    }
    
}
