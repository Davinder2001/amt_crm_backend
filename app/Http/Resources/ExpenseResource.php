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

            'items_batches' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'item_id'  => $item->id,
                        'item_name' => $item->name,
                        'batch_id' => $item->pivot->batch_id ?? null,
                    ];
                });
            }),

            'invoices' => $this->whenLoaded('invoices', fn() => $this->invoices->map(fn($invoice) => [
                'id'   => $invoice->id,
                'name' => $invoice->invoice_number ?? 'Invoice #' . $invoice->id,
            ])),

            'users' => $this->whenLoaded('users', fn() => $this->users->map(fn($user) => [
                'id'   => $user->id,
                'name' => $user->name,
            ])),
        ];
    }
}