<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatUserResource extends JsonResource
{
    public function toArray($request)
    {

        dd($request->user_id ?? 'sdsdsds');
        return [
            'user_id'           => $this->user_id,
            'name'              => $this->user?->name ?? 'Unknown',
            'last_message'      => $this->last_message,
            'last_message_time' => $this->last_message_time,
            'is_read'           => $this->is_read,
            'unread_count'      => $this->unread_count,
        ];
    }
    
}
