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
            'description'       => $this->description,
            'company_id'        => $this->company_id,
            'company_name'      => $this->company?->name,
            'assigned_by_id'    => $this->assigned_by,
            'assigned_by_name'  => $this->assigner?->name,
            'assigned_to_id'    => $this->assigned_to,
            'assigned_to_name'  => $this->assignee?->name,
            'assigned_role'     => $this->assigned_role,
            'start_date'        => $this->start_date->format('d-m-Y'),
            'end_date'          => $this->end_date->format('d-m-Y'),
            'status'            => $this->status,
            'notify'            => $this->notify,
            'attachment_path'   => $this->attachment_path,
            // 'attachment_url'    => $this->attachment_path ? asset('storage/' . $this->attachment_path) : null,
            'attachment_url'    => $this->attachment_path,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
