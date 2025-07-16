<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
    
        $baseImageUrl  = config('app.image_uri');


        return [
            'id'                => $this->id,
            'user_id'           => $this->user_id,
            'company_id'        => $this->company_id,
            'attendance_date'   => $this->attendance_date,
            'clock_in'          => $this->clock_in,
            'clock_in_image'    => $this->clock_in_image
                                   ? $baseImageUrl . '/' . ltrim($this->clock_in_image, '/')
                                   : null,
            'clock_out'         => $this->clock_out,
            'clock_out_image'   => $this->clock_out_image
                                   ? $baseImageUrl . '/' . ltrim($this->clock_out_image, '/')
                                   : null,
            'status'            => $this->status,
            'approval_status'   => $this->approval_status,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'user'              => new UserResource($this->whenLoaded('user')),
        ];
    }
}
