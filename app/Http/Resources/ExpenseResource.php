<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'company_id'  => $this->company_id,
            'heading'     => $this->heading,
            'description' => $this->description,
            'price'       => $this->price,
            'status'      => $this->status,
            'tags'        => $this->tags ?? [],
            'file_url'    => $this->file_url,
        ];
    }
}