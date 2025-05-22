<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerCreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $credits = $this->resource;

        $first = $credits->first();

        if (!$first) {
            return [];
        }

        $customer = $first->customer;

        return [
            'id'         => $customer->id,
            'name'       => $customer->name,
            'email'      => $customer->email,
            'number'     => $customer->number,
            'total_due'  => number_format($credits->sum('outstanding'), 2, '.', ''),
            'credits'    => $credits->map(function ($credit) {
                return [
                    'invoice_number' => $credit->invoice->invoice_number,
                    'invoice_date'   => $credit->invoice->invoice_date,
                    'final_amount'   => $credit->invoice->final_amount,
                    'credit_id'      => $credit->id,
                    'status'         => $credit->status,
                    'amount_paid'    => $credit->amount_paid,
                    'outstanding'    => $credit->outstanding,
                ];
            })->values(),
        ];
    }
}
