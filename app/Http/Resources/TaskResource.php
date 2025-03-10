<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'company_name'      => $this->company->id,
            'assigned_by_name'  => $this->assigner->name,
            'assigned_to_name'  => $this->assignee->name,
            'deadline'          => $this->deadline,
        ];
    }
}
