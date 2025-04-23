<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCredit extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_id',
        'total_due',
        'amount_paid',
        'outstanding',
        'status',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

}
