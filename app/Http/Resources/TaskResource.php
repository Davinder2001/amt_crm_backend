<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray($request)
    {
        $baseUrl = url('/'); // or config('app.url') if you want to use the configured app URL

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'description'       => $this->description,
            'company_name'      => $this->company?->name,
            'assigned_by_id'    => $this->assigned_by,
            'assigned_by_name'  => $this->assigner?->name,
            'assigned_to_id'    => $this->assigned_to,
            'assigned_to_name'  => $this->assignee?->name,
            'assigned_role'     => $this->assigned_role,
            'start_date'        => optional($this->start_date)->format('d-m-Y'),
            'end_date'          => optional($this->end_date)->format('d-m-Y'),
            'status'            => $this->status,
            'notify'            => $this->notify,

            'attachments'       => $this->attachments ?? [],
            'attachment_urls'   => collect($this->attachments ?? [])
                                    ->map(fn($file) => $baseUrl .'/'. ltrim($file, '/'))
                                    ->toArray(),

            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
