<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskHistoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'task_id'        => $this->task_id,
            'task_name'      => $this->task?->name,
            'description'    => $this->description,
            'status'         => $this->status,
            'admin_remark'   => $this->admin_remark,
            'submitted_by'   => $this->submitted_by,
            'submitter_name' => $this->submitter?->name,
            'attachments'    => collect($this->attachments)->map(function ($url) {
                $relativePath = str_replace(url('/') . '/', '', $url);
                $fullPath = public_path($relativePath);

                if (file_exists($fullPath)) {
                    $mime = mime_content_type($fullPath);
                    $content = file_get_contents($fullPath);
                    return 'data:' . $mime . ';base64,' . base64_encode($content);
                }

                return null;
            })->filter()->values(),

            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
