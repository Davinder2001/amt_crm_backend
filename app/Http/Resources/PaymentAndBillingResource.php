<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentAndBillingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'order_id'            => $this->order_id,
            'user_id'             => $this->user_id,
            'transaction_id'      => $this->transaction_id,
            'payment_status'      => $this->payment_status,
            'payment_method'      => $this->payment_method,
            'payment_fail_reason' => $this->payment_fail_reason,
            'payment_reason'      => $this->payment_reason,
            'transaction_amount'  => $this->transaction_amount,
            'payment_date'        => $this->payment_date,
            'payment_time'        => $this->payment_time,
        ];
    }
}
