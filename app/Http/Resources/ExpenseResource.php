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
            'file_url'    => $this->file_url,
            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
            ])),

            'invoices' => $this->whenLoaded('invoices', fn() => $this->invoices->map(fn($invoice) => [
                'id' => $invoice->id,
                'name' => $invoice->invoice_number ?? 'Invoice #' . $invoice->id,
            ])),

            'users' => $this->whenLoaded('users', fn() => $this->users->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])),

        ];
    }
}
